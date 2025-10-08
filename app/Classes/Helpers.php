<?php declare(strict_types=1);

namespace SGWPlugin\Classes;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Exception;

if (!defined("ABSPATH")) die;

class Helpers
{
    /**
     * Конвертирует дату в ISO-формате в массив с датой и временем.
     *
     * @param string $date_time Дата в формате '2025-05-20T09:30:00Z'.
     * @param string|null $timezone Желаемая временная зона (например, 'Europe/Moscow'). Если null, используется UTC.
     *
     * @return array Массив вида ['date' => '2025-05-20', 'time' => '12:30:00'].
     */
    public static function convertIsoDateTime(string $date_time, ?string $timezone = null): array
    {
        // TODO: реализовать получение таймзоны с клиента (куки)
        try {
            $date = new DateTime($date_time);

            if ($timezone) {
                $date->setTimezone(new DateTimeZone($timezone));
            }

            return [
                'date' => $date->format('Y-m-d'),
                'time' => $date->format('H:i:s')
            ];
        } catch (Exception $e) {
            return [
                'date' => gmdate('Y-m-d', strtotime($date_time)),
                'time' => gmdate('H:i:s', strtotime($date_time))
            ];
        }
    }

    /**
     * Конвертирует время из 24-часового формата в 12-часовой (с AM/PM).
     *
     * @param string $time_24h Время в формате 'HH:MM:SS' (например, '18:30:00').
     * @return string Время в формате 'H.MM am/pm' (например, '6.30 pm').
     */
    public static function convertTimeTo12hFormat(string $time_24h): string
    {
        list($hours, $minutes, $seconds) = explode(':', $time_24h);

        $period = ($hours >= 12) ? 'pm' : 'am';
        $hours_12h = ($hours % 12);
        $hours_12h = ($hours_12h == 0) ? 12 : $hours_12h;

        return sprintf('%d.%02d %s', $hours_12h, $minutes, $period);
    }

    /**
     * Возвращает массив дат от текущего дня до этой же даты следующего месяца или предыдущего
     *
     * Метод автоматически корректирует конечную дату, если в следующем месяце
     * нет текущего дня числа (например, 31 января → 28/29 февраля)
     * Формат возвращаемого массива:
     * <code>
     * [
     *      ['date' => '22', 'day' => 'Wed', 'full_date' => '06-22-2025'],
     *      ['date' => '23', 'day' => 'Thu', 'full_date' => '06-23-2025'],
     *      ...
     *      ['date' => '22', 'day' => 'Fri', 'full_date' => '07-22-2025']
     * ]
     * </code>
     *
     * @param string $direction направление месяца 'next' или 'previous'
     * @return array Массив дат с информацией о числе и дне недели
     * @throws Exception В случае ошибок работы с DateTime
     */
    public static function getDatesFromTodayWithRange(string $direction): array
    {
        $today = new DateTime();
        $dates = [];
        $count = 10;

        if ($direction === 'next') {
            for ($i = 0; $i < $count; $i++) {
                $date = (clone $today)->modify("+$i days");
                $dates[] = [
                    'date' => $date->format('d'),
                    'day' => $date->format('D'),
                    'month' => $date->format('F'),
                    'full_date' => $date->format('Y-m-d'),
                ];
            }
        } elseif ($direction === 'previous') {
            for ($i = 0; $i < $count; $i++) {
                $date = (clone $today)->modify("-$i days");
                $dates[] = [
                    'date' => $date->format('d'),
                    'day' => $date->format('D'),
                    'month' => $date->format('F'),
                    'full_date' => $date->format('Y-m-d'),
                ];
            }

            // отсортировать по возрастанию, чтобы шли от старой даты к сегодняшней
            usort($dates, fn($a, $b) => strtotime($b['full_date']) <=> strtotime($a['full_date']));
        }

        return $dates;
    }


    /**
     * Получает URL картинки команды.
     *
     * @param string $teamAbbr Абревиатура названия команды.
     *
     * @return ?string
     */
    public static function getFlag($teamAbbr): ?string
    {
        if (file_exists(SGWPLUGIN_PATH_FLAGS . "/$teamAbbr.webp")) {
            return SGWPLUGIN_URL_FLAGS . "/$teamAbbr.webp";
        }

        return null;
    }

    // Запиненные лиги в сайдбаре
    public static function getPinnedLeagueIds(): array
    {
        $file = __DIR__ . '/../Config/pinned-leagues.php';
        if (!file_exists($file)) return [];
        return include $file;
    }

    public static function urlSlug(string $text): string
    {
        $s = trim($text);

        // если уже слаг — просто привести к нижнему регистру и обрезать края
        if (preg_match('/^[a-z0-9-]+$/i', $s)) {
            return strtolower(trim($s, '-'));
        }

        // Транслитерация
        if (class_exists('\Transliterator')) {
            $tr = \Transliterator::create('Any-Latin; Latin-ASCII;');
            if ($tr) $s = $tr->transliterate($s);
        } elseif (function_exists('iconv')) {
            $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($tmp !== false) $s = $tmp;
        }

        $s = strtolower($s);

        // Нормализация символов
        $s = strtr($s, [
            '&'  => 'and',
            '’'  => "'", '‘' => "'", '´' => "'",
            '–'  => '-', '—' => '-', '−' => '-', '-' => '-', // разные тире → дефис
            '_'  => '-', '/' => '-', '.' => ' ',            // точку/слэш заменим
        ]);

        // Апострофы просто убираем
        $s = str_replace("'", '', $s);

        // Всё не [a-z0-9-] → дефис
        $s = preg_replace('/[^a-z0-9-]+/i', '-', $s);
        // Схлопнуть дефисы
        $s = preg_replace('/-+/', '-', $s);

        return trim($s, '-');
    }
}