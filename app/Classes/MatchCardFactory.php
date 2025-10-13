<?php declare(strict_types=1);

namespace SGWPlugin\Classes;

class MatchCardFactory
{
    public static function build(array $event, string $status): array
    {
        // подстраховка competitionId
        if (empty($event['competitionId']) && !empty($event['competition']['id'])) {
            $event['competitionId'] = (int)$event['competition']['id'];
        }

        $card = [
            'badge' => ucfirst($status),   // Live/Upcoming/Finished
            'teams' => [],
            'url'   => MatchUrl::build($event),
        ];

        // если в конце уже нет -c{cid}, добавим
        $cid = $event['competitionId'] ?? null;
        if (!empty($card['url']) && $cid) {
            if (!preg_match('~\-c\d+\/?$~', $card['url'])) {
                $card['url'] = rtrim($card['url'], '/') . "-c{$cid}/";
            }
        }

        $defaultLogo = SGWPLUGIN_URL_FRONT . '/images/content/team-placeholder.png';

        $t1 = $event['competitors'][0] ?? null;
        $t2 = $event['competitors'][1] ?? null;

        if ($t1 && $t2) {
            $logo1 = Helpers::getFlag($t1['abbreviation'] ?? '') ?: $defaultLogo;
            $logo2 = Helpers::getFlag($t2['abbreviation'] ?? '') ?: $defaultLogo;

            $card['teams'] = [
                [
                    'name'  => $t1['name'] ?? '',
                    'logo'  => $logo1,
                    'score' => in_array($status, ['live','finished'], true) ? ($t1['score'] ?? '') : '',
                ],
                [
                    'name'  => $t2['name'] ?? '',
                    'logo'  => $logo2,
                    'score' => in_array($status, ['live','finished'], true) ? ($t2['score'] ?? '') : '',
                ],
            ];
        }

        if (!empty($event['date'])) {
            $iso = Helpers::convertIsoDateTime($event['date']); // ['date'=>'Y-m-d','time'=>'H:i:s']

            if ($status === 'upcoming') {
                // Показываем дату и время
                $card['date_iso'] = $event['date'];
                $card['date']     = date('M j, Y', strtotime($iso['date']));
                $card['time']     = Helpers::convertTimeTo12hFormat($iso['time']);

            } elseif ($status === 'live') {
                // Показываем минуту матча — берём только elapsed
                $elapsed = $event['detailedStatus']['elapsed'] ?? ($event['elapsed'] ?? null);

                if (is_numeric($elapsed)) {
                    $card['live_minute'] = ((int)$elapsed) . "’";
                } elseif (is_string($elapsed) && preg_match('/^\d+(?:\+\d+)?$/', $elapsed)) {
                    // поддержка остановочного времени вида 45+2
                    $card['live_minute'] = $elapsed . "’";
                } else {
                    $card['live_minute'] = 'Live';
                }

            } else { // finished
                // Короткая подпись для завершённых
                $card['meta_note'] = 'Played on ' . Helpers::convertTimeTo12hFormat($iso['time']);
            }
        }

        return $card;
    }
}
