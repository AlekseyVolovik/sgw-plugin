<?php declare(strict_types=1);

namespace SGWPlugin\Classes;

class MatchCardFactory
{
    /**
     * @param array  $event
     * @param string $status 'live'|'upcoming'|'finished'
     */
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

        // добавляем -c{cid} в URL при необходимости
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

        // === Единообразная дата/время для всех статусов ===
        if (!empty($event['date'])) {
            $iso = Helpers::convertIsoDateTime($event['date']); // ['date'=>'Y-m-d','time'=>'H:i:s']

            $card['date_iso'] = $event['date'];
            if (!empty($iso['date'])) {
                $card['date'] = date('M j, Y', strtotime($iso['date']));
            }
            if (!empty($iso['time'])) {
                $card['time'] = Helpers::convertTimeTo12hFormat($iso['time']);
            }

            // Фолбэк-строка, если где-то отображается meta_note
            if ($status === 'live') {
                $card['meta_note'] = 'Started at ' . ($card['time'] ?? '');
            } elseif ($status === 'finished') {
                $card['meta_note'] = 'Played on ' . ($card['time'] ?? '');
            }
        }

        return $card;
    }
}
