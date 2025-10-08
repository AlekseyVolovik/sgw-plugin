<?php declare(strict_types=1);

namespace SGWPlugin\Classes;

class MatchUrl
{
    public static function build(array $event): ?string
    {
        $base = '/football/match/';

        // 1) Каноника: если есть сегмент от API — используем его как есть
        if (!empty($event['urlSegment'])) {
            return $base . trim((string)$event['urlSegment'], '/') . '/';
        }

        // 2) Нужны названия команд для fallback-слугов
        $team1 = $event['competitors'][0]['name'] ?? null;
        $team2 = $event['competitors'][1]['name'] ?? null;
        if (!$team1 || !$team2) return null;

        $t1 = Helpers::urlSlug($team1);
        $t2 = Helpers::urlSlug($team2);

        // 3) Предпочитаем числовой ID (id | eventId | externalId)
        $anyId = $event['id'] ?? ($event['eventId'] ?? ($event['externalId'] ?? null));
        if ($anyId) {
            return "{$base}{$t1}-vs-{$t2}-{$anyId}/";
        }

        // 4) Иначе — дата в формате YYYY-MM-DD (НЕ штамп)
        if (!empty($event['date'])) {
            try {
                $d = (new \DateTime($event['date']))->format('Y-m-d');
                return "{$base}{$t1}-vs-{$t2}-{$d}/";
            } catch (\Exception $e) {
                // игнорируем и идём в последний фолбэк
            }
        }

        // 5) Последний фолбэк (без id и даты)
        return "{$base}{$t1}-vs-{$t2}/";
    }
}
