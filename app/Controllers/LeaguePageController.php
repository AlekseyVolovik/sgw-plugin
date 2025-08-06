<?php declare(strict_types=1);

namespace SGWPlugin\Controllers;

use SGWClient;
use SGWPlugin\Classes\Fields;
use SGWPlugin\Classes\Helpers;
use SGWPlugin\Classes\Twig;
use SGWPlugin\Classes\MetaBuilder;

class LeaguePageController
{
    private SGWClient $sgw;
    private ?int $projectId;
    private ?string $sport;
    private ?string $leagueSlug;
    private ?string $countrySlug;

    public function __construct(array $params)
    {
        $this->sgw = SGWClient::getInstance();
        $this->projectId = Fields::get_general_project_id();
        $this->sport = Fields::get_general_sport();
        $this->leagueSlug = $params['league'] ?? null;
        $this->countrySlug = $params['country'] ?? null;
    }

    private function getLeagueData(): ?array
    {
        if (!$this->projectId || !$this->sport || !$this->leagueSlug || !$this->countrySlug) return null;

        $response = $this->sgw->api->matchcentre->getMatchCentreCategories($this->projectId, $this->sport);
        if (empty($response['data'])) return null;

        foreach ($response['data'] as $item) {
            if ($item['entityType'] !== 'Competition') continue;

            $segments = $item['urlSegments'] ?? [];
            if (count($segments) < 2) continue;

            if ($segments[0] === $this->countrySlug && $segments[1] === $this->leagueSlug) {
                return $item;
            }
        }

        return null;
    }


    private function getLeagueEvents(int $competitionId): array
    {
        if (!$this->projectId || !$this->sport) return [];

        $grouped = [
            'live' => [],
            'upcoming' => [],
            'finished' => [],
        ];

        // LIVE матчи
        $live = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, [
            'status' => 'live',
            'competitionId' => $competitionId
        ]);
        if (!empty($live['success']) && !empty($live['data']['data'])) {
            $grouped['live'] = $live['data']['data'];
        }

        // UPCOMING матчи
        $upcoming = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, [
            'period' => 'upcoming',
            'competitionId' => $competitionId
        ]);
        if (!empty($upcoming['success']) && !empty($upcoming['data']['data'])) {
            $grouped['upcoming'] = $upcoming['data']['data'];
        }

        // FINISHED матчи
        $finished = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, [
            'period' => 'finished',
            'competitionId' => $competitionId
        ]);
        if (!empty($finished['success']) && !empty($finished['data']['data'])) {
            $grouped['finished'] = $finished['data']['data'];
        }

        return $grouped;
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

    private function transformEventsToMatchCards(array $events, string $modify): array
    {
        $cards = [];
        foreach ($events as $event) {
            $cards[] = CatalogController::getEventCardContent($event, $modify); // static call or reuse
        }
        return $cards;
    }

    public function render(): ?string
    {
        $league = $this->getLeagueData();
        if (!$league) return "<div>League Not Found</div>";

        // Установить title
        $titleTemplate = MetaBuilder::getTemplate('league', 'title');
        if ($titleTemplate) {
            $title = MetaBuilder::buildMeta($titleTemplate, [
                'league' => $league['name'],
                'site_name' => get_bloginfo('name')
            ]);
            MetaBuilder::setTitle($title);
        }

        // Установить description
        $descTemplate = MetaBuilder::getTemplate('league', 'description');
        if ($descTemplate) {
            $description = MetaBuilder::buildMeta($descTemplate, [
                'league' => $league['name'],
                'site_name' => get_bloginfo('name'),
                'country' => ucfirst($this->countrySlug)
            ]);
            MetaBuilder::setDescription($description);
        }

        $events = $this->getLeagueEvents((int)$league['entityId']);

        $matchCardsByStatus = [
            'live' => array_map(fn($e) => $this->getEventCardContent($e, 'live'), $events['live']),
            'upcoming' => array_map(fn($e) => $this->getEventCardContent($e, 'upcoming'), $events['upcoming']),
            'finished' => array_map(fn($e) => $this->getEventCardContent($e, 'finished'), $events['finished']),
        ];

        return Twig::render('pages/league/view.twig', [
            'countrySlug' => $this->countrySlug,
            'leagueName' => $league['name'],
            'filters_leagues_by_country' => $this->getGroupedLeaguesByCountry(),
            'match_cards_by_status' => $matchCardsByStatus,
            'active_status' => 'live',
            'pinned_leagues' => $this->getPinnedLeagues(),
        ]);
    }
}
