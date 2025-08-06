<?php declare(strict_types=1);

namespace SGWPlugin\Controllers;

use SGWClient;
use SGWPlugin\Classes\Fields;
use SGWPlugin\Classes\Helpers;
use SGWPlugin\Classes\Twig;
use SGWPlugin\Classes\MetaBuilder;

class CountryPageController
{
    private SGWClient $sgw;
    private ?int $projectId;
    private ?string $sport;
    private ?string $countrySlug;

    public function __construct(array $params)
    {
        $this->sgw = SGWClient::getInstance();
        $this->projectId = Fields::get_general_project_id();
        $this->sport = Fields::get_general_sport();
        $this->countrySlug = $params['country'] ?? null;
    }

    private function getGroupedLeaguesByCountry(): array
    {
        $result = [];

        if (!$this->projectId || !$this->sport) return $result;

        $response = $this->sgw->api->matchcentre->getMatchCentreCategories($this->projectId, $this->sport);

        if (isset($response['data'])) {
            foreach ($response['data'] as $item) {
                if ($item['entityType'] !== 'Competition') continue;

                $segments = $item['urlSegments'] ?? [];
                if (count($segments) < 2) continue;

                $country = $segments[0];
                $league = $segments[1];

                $result[$country][] = [
                    'title' => $item['name'],
                    'url' => "/football/{$country}/{$league}/"
                ];
            }
        }

        return $result;
    }

    private function getPinnedLeagues(): array
    {
        $result = [];
        $ids = Helpers::getPinnedLeagueIds();

        if (!$this->projectId || !$this->sport || empty($ids)) return $result;

        $response = $this->sgw->api->matchcentre->getMatchCentreCategories($this->projectId, $this->sport);

        if (isset($response['data'])) {
            foreach ($response['data'] as $item) {
                if ($item['entityType'] !== 'Competition') continue;
                if (!in_array($item['entityId'], $ids)) continue;

                $segments = $item['urlSegments'] ?? [];
                if (count($segments) < 2) continue;

                $country = $segments[0];
                $league = $segments[1];

                $result[] = [
                    'title' => $item['name'],
                    'url' => "/football/{$country}/{$league}/"
                ];
            }
        }

        return $result;
    }

    private function getEventCardContent(array $event, string $modify): array
    {
        $card = [
            'venues' => [],
        ];

        $card['title'] = $event['competition'] ?? '';

        if ($modify === 'live') $card['badge'] = 'Live';
        elseif ($modify === 'upcoming') $card['badge'] = 'Upcoming';
        elseif ($modify === 'finished') $card['badge'] = 'Finished';

        if (isset($event['date']) && in_array($modify, ['live', 'finished'])) {
            $date = \SGWPlugin\Classes\Helpers::convertIsoDateTime($event['date']);
            $card['venues'][] = [
                'icon' => sprintf('url(%s/images/content/calendar.svg)', SGWPLUGIN_URL_FRONT),
                'text' => ($modify === 'live' ? 'The match started at ' : 'The match was held on ') . \SGWPlugin\Classes\Helpers::convertTimeTo12hFormat($date['time']),
            ];
        }

        $defaultLogo = SGWPLUGIN_URL_FRONT . '/images/content/team-placeholder.png';

        if (!empty($event['competitors'][0]) && !empty($event['competitors'][1])) {
            $team1 = $event['competitors'][0];
            $team2 = $event['competitors'][1];

            $logo1 = \SGWPlugin\Classes\Helpers::getFlag($team1['abbreviation']) ?: $defaultLogo;
            $logo2 = \SGWPlugin\Classes\Helpers::getFlag($team2['abbreviation']) ?: $defaultLogo;

            $card['teams'] = [
                [
                    'name' => $team1['name'],
                    'logo' => $logo1,
                    'score' => in_array($modify, ['live', 'finished']) ? ($team1['score'] ?? '') : ''
                ],
                [
                    'name' => $team2['name'],
                    'logo' => $logo2,
                    'score' => in_array($modify, ['live', 'finished']) ? ($team2['score'] ?? '') : ''
                ]
            ];
        }

        if (isset($event['date']) && $modify === 'upcoming') {
            $date = \SGWPlugin\Classes\Helpers::convertIsoDateTime($event['date']);

            $card['date_atr'] = $event['date'];
            $card['date'] = date('M j, Y', strtotime($date['date']));
            $card['time'] = \SGWPlugin\Classes\Helpers::convertTimeTo12hFormat($date['time']);
        }

        return $card;
    }

    public function render(): ?string
    {
        if (!$this->projectId || !$this->sport || !$this->countrySlug) {
            return "<div>Invalid Country Page</div>";
        }

        // Получаем лиги по стране
        $response = $this->sgw->api->matchcentre->getMatchCentreCategories($this->projectId, $this->sport);
        if (empty($response['data'])) return "<div>No data</div>";

        $competitionIds = [];
        foreach ($response['data'] as $item) {
            if ($item['entityType'] !== 'Competition') continue;
            $segments = $item['urlSegments'] ?? [];
            if (count($segments) < 2) continue;

            if ($segments[0] === $this->countrySlug) {
                $competitionIds[] = $item['entityId'];
            }
        }

        // Установка мета-тегов (title и description) для country-страницы
        $templateTitle = MetaBuilder::getTemplate('football_country', 'title');
        if ($templateTitle) {
            MetaBuilder::setTitle(MetaBuilder::buildMeta($templateTitle, [
                'country' => ucfirst($this->countrySlug),
                'site_name' => get_bloginfo('name')
            ]));
        }

        $templateDesc = MetaBuilder::getTemplate('football_country', 'description');
        if ($templateDesc) {
            MetaBuilder::setDescription(MetaBuilder::buildMeta($templateDesc, [
                'country' => ucfirst($this->countrySlug),
                'site_name' => get_bloginfo('name')
            ]));
        }

        if (empty($competitionIds)) return "<div>No competitions for country</div>";

        // Получаем события
        $grouped = ['live' => [], 'upcoming' => [], 'finished' => []];
        foreach (['live' => ['status' => 'live'], 'upcoming' => ['period' => 'upcoming'], 'finished' => ['period' => 'finished']] as $status => $params) {
            foreach ($competitionIds as $competitionId) {
                $params['competitionId'] = $competitionId;
                $res = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, $params);

                if (!empty($res['success']) && !empty($res['data']['data'])) {
                    foreach ($res['data']['data'] as $event) {
                        $grouped[$status][] = $this->getEventCardContent($event, $status);
                    }
                }
            }
        }
        
        // Ограничение: максимум 15 upcoming и finished матчей
        $grouped['upcoming'] = array_slice($grouped['upcoming'], 0, 15);
        $grouped['finished'] = array_slice($grouped['finished'], 0, 15);

        return Twig::render('pages/country/view.twig', [
            'countrySlug' => $this->countrySlug,
            'filters_leagues_by_country' => $this->getGroupedLeaguesByCountry(),
            'match_cards_by_status' => $grouped,
            'active_status' => 'live',
            'pinned_leagues' => $this->getPinnedLeagues(),
        ]);
    }
}
