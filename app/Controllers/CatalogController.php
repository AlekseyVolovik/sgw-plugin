<?php declare(strict_types=1);

namespace SGWPlugin\Controllers;

use DateTime;
use SGWClient;
use SGWPlugin\Classes\Fields;
use SGWPlugin\Classes\Helpers;
use SGWPlugin\Classes\Twig;
use SGWPlugin\Classes\MetaBuilder;

if (!defined("ABSPATH")) die;

class CatalogController
{
    private SGWClient $sgw;
    private ?int $projectId;
    private ?string $sport;
    private ?string $baseUrl;
    private ?string $period;
    private ?string $status;
    private ?string $date;

    function __construct(array $params)
    {
        $this->sgw = SGWClient::getInstance();
        $this->projectId = Fields::get_general_project_id();
        $this->sport = Fields::get_general_sport();

        $this->baseUrl = $params['entry'] ?? null;
        $this->period = $params['period'] ?? null;
        $this->status = $params['status'] ?? null;
        $this->date = $params['date'] ?? null;
    }

    private function getFilters(): array
    {
        return [
            'days' => $this->getFiltersDay(),
            'status' => $this->getFiltersStatus(),
            'calendar' => $this->getFiltersCalendar(),
            'leagues_by_country' => $this->getGroupedLeaguesByCountry(),
        ];
    }

    private function getEvents(): array
    {
        if ($this->period === 'today') return $this->getEventsTodayTab();
        if ($this->period === 'tomorrow') return $this->getEventsTomorrowTab();
        if ($this->period === 'yesterday') return $this->getEventsYesterdayTab();

        return match ($this->status) {
            default => $this->getAllEventsTabs(),
            'live' => $this->getEventsLiveTab(),
            'upcoming' => $this->getEventsUpcomingTab(),
            'finished' => $this->getEventsFinishedTab()         
        };
    }

    private function getAllEventsTabs(): array
    {
        $tabs = [];

        $live = $this->getEventsLiveTab();
        if (!empty($live)) {
            $tabs = array_merge($tabs, $live);
        }

        $upcoming = $this->getEventsUpcomingTab();
        if (!empty($upcoming)) {
            $tabs = array_merge($tabs, $upcoming);
        }

        $finished = $this->getEventsFinishedTab();
        if (!empty($finished)) {
            $tabs = array_merge($tabs, $finished);
        }

        return $tabs;
    }

    private function getEventsYesterdayTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        $date = date('Y-m-d', strtotime('-1 day'));

        $response = $this->sgw->api->matchcentre->getMatchCentreEvents(
            $this->projectId,
            $this->sport,
            ['period' => 'finished', 'fromDate' => $date, 'toDate' => $date]
        );

        $groups = [];

        if (!empty($response['success']) && !empty($response['data']['data'])) {
            foreach ($response['data']['data'] as $event) {
                $formattedDate = isset($event['date']) ? (new DateTime($event['date']))->format('d M, Y') : 'Unknown Date';

                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));

                $league = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$country}|{$league}|{$formattedDate}";

                $groups[$groupKey][] = $this->getEventCardContent($event, 'finished');
            }
        }

        ksort($groups);

        $result = [];

        foreach ($groups as $key => $matchCards) {
            [$country, $league, $formattedDate] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_date' => $formattedDate,
                'match_cards' => $matchCards,
            ];
        }

        return $result;
    }

    private function getEventsTodayTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        $tabs = [];

        $date = date('Y-m-d');
        $formattedDate = date('d M, Y');

        // --- UPCOMING TODAY ---
        $upcomingResponse = $this->sgw->api->matchcentre->getMatchCentreEvents(
            $this->projectId,
            $this->sport,
            ['period' => 'today']
        );

        if (!empty($upcomingResponse['success']) && !empty($upcomingResponse['data']['data'])) {
            $groups = [];

            foreach ($upcomingResponse['data']['data'] as $event) {
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$country}|{$league}|{$formattedDate}";

                $groups[$groupKey][] = $this->getEventCardContent($event, 'upcoming');
            }

            ksort($groups);

            foreach ($groups as $key => $matchCards) {
                [$country, $league, $dateText] = explode('|', $key);

                $tabs[] = [
                    'title_country_league' => "{$country}: {$league}",
                    'title_date' => $dateText,
                    'match_cards' => $matchCards,
                ];
            }
        }

        // --- FINISHED TODAY ---
        $finishedResponse = $this->sgw->api->matchcentre->getMatchCentreEvents(
            $this->projectId,
            $this->sport,
            ['period' => 'finished', 'fromDate' => $date, 'toDate' => $date]
        );

        if (!empty($finishedResponse['success']) && !empty($finishedResponse['data']['data'])) {
            $groups = [];

            foreach ($finishedResponse['data']['data'] as $event) {
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$country}|{$league}|{$formattedDate}";

                $groups[$groupKey][] = $this->getEventCardContent($event, 'finished');
            }

            ksort($groups);

            foreach ($groups as $key => $matchCards) {
                [$country, $league, $dateText] = explode('|', $key);

                $tabs[] = [
                    'title_country_league' => "{$country}: {$league}",
                    'title_date' => $dateText,
                    'match_cards' => $matchCards,
                ];
            }
        }

        return $tabs;
    }

    private function getEventsTomorrowTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        $date = date('Y-m-d', strtotime('+1 day'));
        $formattedDate = date('d M, Y', strtotime($date));

        $response = $this->sgw->api->matchcentre->getMatchCentreEvents(
            $this->projectId,
            $this->sport,
            ['period' => 'upcoming', 'fromDate' => $date, 'toDate' => $date]
        );

        $groups = [];

        if (!empty($response['success']) && !empty($response['data']['data'])) {
            foreach ($response['data']['data'] as $event) {
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$country}|{$league}|{$formattedDate}";

                $groups[$groupKey][] = $this->getEventCardContent($event, 'upcoming');
            }
        }

        ksort($groups);

        $result = [];
        foreach ($groups as $key => $matchCards) {
            [$country, $league, $dateText] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_date' => $dateText,
                'match_cards' => $matchCards,
            ];
        }

        return $result;
    }

    private function getEventsLiveTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        $response = $this->sgw->api->matchcentre->getMatchCentreEvents(
            $this->projectId,
            $this->sport,
            ['status' => 'live']
        );

        $groups = [];

        if (!empty($response['success']) && !empty($response['data']['data'])) {
            foreach ($response['data']['data'] as $event) {
                // Получение страны и лиги
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));

                $league = $event['competition'] ?? 'Unknown League';

                // Ключ группировки
                $groupKey = "{$country}|{$league}";

                $groups[$groupKey][] = $this->getEventCardContent($event, 'live');
            }
        }

        ksort($groups);

        $result = [];

        foreach ($groups as $key => $matchCards) {
            [$country, $league] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_date' => '', // нет даты у live
                'match_cards' => $matchCards,
            ];
        }

        return $result;
    }

    private function getEventsUpcomingTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        $params = ['period' => 'upcoming'];

        if ($this->date) {
            $params['fromDate'] = $this->date;
            $params['toDate'] = $this->date;
        }

        $response = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, $params);

        $groups = [];

        if (!empty($response['success']) && !empty($response['data']['data'])) {
            foreach ($response['data']['data'] as $event) {
                // Получаем дату
                $eventDate = isset($event['date']) ? (new DateTime($event['date']))->format('Y-m-d') : '0000-00-00';
                $formattedDate = isset($event['date']) ? (new DateTime($event['date']))->format('d M, Y') : 'Unknown Date';

                // Получаем страну и лигу
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));

                $league = $event['competition'] ?? 'Unknown League';

                // Ключ с разделением
                $groupKey = "{$eventDate}|{$country}|{$league}|{$formattedDate}";

                $groups[$groupKey][] = $this->getEventCardContent($event, 'upcoming');
            }
        }

        // Сортировка по дате
        ksort($groups);

        $result = [];

        foreach ($groups as $key => $matchCards) {
            [$eventDate, $country, $league, $dateText] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_date' => $dateText,
                'match_cards' => $matchCards,
            ];
        }

        return $result;
    }

    private function getEventsFinishedTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        $params = ['period' => 'finished'];

        if ($this->date) {
            $params['fromDate'] = $this->date;
            $params['toDate'] = $this->date;
        }

        $response = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, $params);

        $groups = [];

        if (!empty($response['success']) && !empty($response['data']['data'])) {
            foreach ($response['data']['data'] as $event) {
                // Дата
                $formattedDate = isset($event['date']) ? (new DateTime($event['date']))->format('d M, Y') : 'Unknown Date';

                // Страна и лига
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));

                $league = $event['competition'] ?? 'Unknown League';

                // Ключ: дата + страна + лига
                $groupKey = "{$country}|{$league}|{$formattedDate}";

                $groups[$groupKey][] = $this->getEventCardContent($event, 'finished');
            }
        }

        $result = [];

        foreach ($groups as $key => $matchCards) {
            [$country, $league, $dateText] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_date' => $dateText,
                'match_cards' => $matchCards,
            ];
        }

        return $result;
    }

    private function getFiltersDay(): array
    {
        return [
            [
                'url' => "/$this->baseUrl/yesterday/",
                'title' => 'Yesterday',
                'state' => $this->period === 'yesterday' ? 'active' : null
            ],
            [
                'url' => "/$this->baseUrl/today/",
                'title' => 'Today',
                'state' => $this->period === 'today' ? 'active' : null
            ],
            [
                'url' => "/$this->baseUrl/tomorrow/",
                'title' => 'Tomorrow',
                'state' => $this->period === 'tomorrow' ? 'active' : null
            ],
        ];
    }

    private function getFiltersStatus(): array
    {
        return [
            [
                'url' => "/$this->baseUrl/",
                'title' => 'All',
                'state' => $this->status === null ? 'active' : null
            ],
            [
                'url' => "/$this->baseUrl/live/",
                'title' => 'Live',
                'state' => $this->status === 'live' ? 'active' : null,
                'class' => 'live'
            ],
            [
                'url' => "/$this->baseUrl/upcoming/",
                'title' => 'Upcoming',
                'state' => $this->status === 'upcoming' ? 'active' : null
            ],
            [
                'url' => "/$this->baseUrl/finished/",
                'title' => 'Finished',
                'state' => $this->status === 'finished' ? 'active' : null
            ]
        ];
    }

    private function getFiltersCalendar(): array
    {
        if ($this->status === 'upcoming' || $this->status === 'finished') {
            $dates = Helpers::getDatesFromTodayWithRange($this->status === 'upcoming' ? 'next' : 'previous');
            $calendarIcon = sprintf('%s/images/content/calendar-icon.png', SGWPLUGIN_URL_FRONT);

            $activeSet = false;
            foreach ($dates as $key => $date) {
                $isActive = $date['full_date'] === $this->date;

                $dates[$key]['url'] = "/$this->baseUrl/$this->status/{$date['full_date']}/";
                $dates[$key]['active'] = $isActive;
                $dates[$key]['icon'] = $calendarIcon;

                if ($isActive) $activeSet = true;
            }

            if (!$activeSet && count($dates) > 0) {
                $dates[0]['active'] = true;
            }

            return $dates;
        }

        return [];
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
                    'url' => "/{$this->baseUrl}/{$country}/{$league}/"
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
                    'url' => "/{$this->baseUrl}/{$country}/{$league}/"
                ];
            }
        }

        return $result;
    }

    private function getEventCardContent(array $event, string $modify): array
    {
        if (empty($event['competitionId']) && !empty($event['competition']['id'])) {
            $event['competitionId'] = (int)$event['competition']['id'];
        }

        return \SGWPlugin\Classes\MatchCardFactory::build($event, $modify);
    }

    public function render(): null|string
    {
        $titleKey = $this->period ?: $this->status ?: 'football';

        $metaVars = [
            'site_name' => get_bloginfo('name')
        ];

        if ($this->date && in_array($this->status, ['finished', 'upcoming'])) {
            $parsedDate = \DateTime::createFromFormat('Y-m-d', $this->date);
            if ($parsedDate) {
                $formattedDate = $parsedDate->format('F j, Y');
                $metaVars['date'] = $formattedDate;

                $titleKey = "{$this->status}_with_date";
            }
        }

        $titleTemplate = MetaBuilder::getTemplate($titleKey, 'title');
        if ($titleTemplate) {
            MetaBuilder::setTitle(MetaBuilder::buildMeta($titleTemplate, $metaVars));
        }

        $descriptionTemplate = MetaBuilder::getTemplate($titleKey, 'description');
        if ($descriptionTemplate) {
            MetaBuilder::setDescription(MetaBuilder::buildMeta($descriptionTemplate, $metaVars));
        }

        $events = $this->getEvents();

        $context = [
            'filters' => $this->getFilters(),
            'events' => $events,
            'pinned_leagues' => $this->getPinnedLeagues(), 
        ];

        if (empty($events)) {
            $context['no_matches_card'] = [
                'title' => 'No matches available',
                'subtitle' => 'Please check back later or select another date.',
            ];
        }

        return Twig::render('pages/catalog/view.twig', $context);
    }
}
