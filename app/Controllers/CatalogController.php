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
            'combined' => $this->getFiltersCombined(),
            'calendar' => $this->getFiltersCalendar(),
            'leagues_by_country' => $this->getGroupedLeaguesByCountry(),
            // (опционально) оставим старые для обратной совместимости, но в шаблон их больше не прокидываем:
            // 'status' => $this->getFiltersStatus(),
            // 'days'   => $this->getFiltersDay(),
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
                $formattedDate = isset($event['date'])
                    ? (new DateTime($event['date']))->format('d M, Y')
                    : 'Unknown Date';

                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$country}|{$league}|{$formattedDate}";

                if (!isset($groups[$groupKey]['_pin'])) {
                    $groups[$groupKey]['_pin'] = $this->buildPinMetaFromEvent($event);
                }

                $groups[$groupKey]['cards'][] = $this->getEventCardContent($event, 'finished');
            }
        }

        ksort($groups);

        $result = [];
        foreach ($groups as $key => $pack) {
            [$country, $league, $formattedDate] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_country'        => $country,
                'title_league'         => $league,
                'title_date'           => $formattedDate,
                'match_cards'          => $pack['cards'] ?? [],
                'pin'                  => $pack['_pin'] ?? ['id'=>null,'slug'=>null,'title'=>null,'url'=>null],
            ];
        }

        return $result;
    }

    private function getEventsTodayTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        $date = date('Y-m-d');
        $formattedDate = date('d M, Y');

        $groups = [];

        // UPCOMING TODAY
        $upcomingResponse = $this->sgw->api->matchcentre->getMatchCentreEvents(
            $this->projectId,
            $this->sport,
            ['period' => 'today']
        );

        if (!empty($upcomingResponse['success']) && !empty($upcomingResponse['data']['data'])) {
            foreach ($upcomingResponse['data']['data'] as $event) {
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$country}|{$league}|{$formattedDate}";

                if (!isset($groups[$groupKey]['_pin'])) {
                    $groups[$groupKey]['_pin'] = $this->buildPinMetaFromEvent($event);
                }

                $groups[$groupKey]['cards'][] = $this->getEventCardContent($event, 'upcoming');
            }
        }

        // FINISHED TODAY
        $finishedResponse = $this->sgw->api->matchcentre->getMatchCentreEvents(
            $this->projectId,
            $this->sport,
            ['period' => 'finished', 'fromDate' => $date, 'toDate' => $date]
        );

        if (!empty($finishedResponse['success']) && !empty($finishedResponse['data']['data'])) {
            foreach ($finishedResponse['data']['data'] as $event) {
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$country}|{$league}|{$formattedDate}";

                if (!isset($groups[$groupKey]['_pin'])) {
                    $groups[$groupKey]['_pin'] = $this->buildPinMetaFromEvent($event);
                }

                $groups[$groupKey]['cards'][] = $this->getEventCardContent($event, 'finished');
            }
        }

        ksort($groups);

        $tabs = [];
        foreach ($groups as $key => $pack) {
            [$country, $league, $dateText] = explode('|', $key);

            $tabs[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_country'        => $country,
                'title_league'         => $league,
                'title_date'           => $dateText,
                'match_cards'          => $pack['cards'] ?? [],
                'pin'                  => $pack['_pin'] ?? ['id'=>null,'slug'=>null,'title'=>null,'url'=>null],
            ];
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

                if (!isset($groups[$groupKey]['_pin'])) {
                    $groups[$groupKey]['_pin'] = $this->buildPinMetaFromEvent($event);
                }

                $groups[$groupKey]['cards'][] = $this->getEventCardContent($event, 'upcoming');
            }
        }

        ksort($groups);

        $result = [];
        foreach ($groups as $key => $pack) {
            [$country, $league, $dateText] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_country'        => $country,
                'title_league'         => $league,
                'title_date'           => $dateText,
                'match_cards'          => $pack['cards'] ?? [],
                'pin'                  => $pack['_pin'] ?? ['id'=>null,'slug'=>null,'title'=>null,'url'=>null],
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
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league  = $event['competition'] ?? 'Unknown League';
                $groupKey = "{$country}|{$league}";

                if (!isset($groups[$groupKey]['_pin'])) {
                    $groups[$groupKey]['_pin'] = $this->buildPinMetaFromEvent($event);
                }

                $groups[$groupKey]['cards'][] = $this->getEventCardContent($event, 'live');
            }
        }

        ksort($groups);

        $result = [];

        foreach ($groups as $key => $pack) {
            [$country, $league] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_country'        => $country,
                'title_league'         => $league,
                'title_date'           => '',
                'match_cards'          => $pack['cards'] ?? [],
                'pin'                  => $pack['_pin'] ?? ['id'=>null,'slug'=>null,'title'=>null,'url'=>null],
            ];
        }

        return $result;
    }

    private function getEventsUpcomingTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        // если дата не передана — берём сегодня
        $targetDate = $this->date ?: date('Y-m-d');

        $params = [
            'period'   => 'upcoming',
            'fromDate' => $targetDate,
            'toDate'   => $targetDate,
        ];

        $response = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, $params);

        $groups = [];
        if (!empty($response['success']) && !empty($response['data']['data'])) {
            $formattedDate = date('d M, Y', strtotime($targetDate));

            foreach ($response['data']['data'] as $event) {
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league  = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$targetDate}|{$country}|{$league}|{$formattedDate}";

                // meta для pin — один раз на группу
                if (!isset($groups[$groupKey]['_pin'])) {
                    $groups[$groupKey]['_pin'] = $this->buildPinMetaFromEvent($event);
                }

                // карточки матчей
                $groups[$groupKey]['cards'][] = $this->getEventCardContent($event, 'upcoming');
            }
        }

        ksort($groups);

        $result = [];
        foreach ($groups as $key => $pack) {
            [, $country, $league, $dateText] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_country'        => $country,
                'title_league'         => $league,
                'title_date'           => $dateText,
                'match_cards'          => $pack['cards'] ?? [],
                'pin'                  => $pack['_pin'] ?? ['id'=>null,'slug'=>null,'title'=>null,'url'=>null],
            ];
        }

        return $result;
    }

    private function getEventsFinishedTab(): array
    {
        if (!$this->projectId || !$this->sport) return [];

        // если дата не передана — берём сегодня
        $targetDate = $this->date ?: date('Y-m-d');

        $params = [
            'period'   => 'finished',
            'fromDate' => $targetDate,
            'toDate'   => $targetDate,
        ];

        $response = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, $params);

        $groups = [];
        if (!empty($response['success']) && !empty($response['data']['data'])) {
            $formattedDate = date('d M, Y', strtotime($targetDate));

            foreach ($response['data']['data'] as $event) {
                $country = $event['league'] ?? ($event['urlSegments'][0] ?? 'Unknown Country');
                $country = ucfirst(str_replace('-', ' ', $country));
                $league  = $event['competition'] ?? 'Unknown League';

                $groupKey = "{$country}|{$league}|{$formattedDate}";

                // meta для pin — один раз на группу
                if (!isset($groups[$groupKey]['_pin'])) {
                    $groups[$groupKey]['_pin'] = $this->buildPinMetaFromEvent($event);
                }

                // карточки матчей
                $groups[$groupKey]['cards'][] = $this->getEventCardContent($event, 'finished');
            }
        }

        ksort($groups);

        $result = [];
        foreach ($groups as $key => $pack) {
            [$country, $league, $dateText] = explode('|', $key);

            $result[] = [
                'title_country_league' => "{$country}: {$league}",
                'title_country'        => $country,
                'title_league'         => $league,
                'title_date'           => $dateText,
                'match_cards'          => $pack['cards'] ?? [],
                'pin'                  => $pack['_pin'] ?? ['id'=>null,'slug'=>null,'title'=>null,'url'=>null],
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

    private function getFiltersCombined(): array
    {
        // 1) Статусы как есть
        $status = $this->getFiltersStatus(); // ['url','title','state','class?']

        // 2) Дни → привести к формату статуса
        $daysRaw = $this->getFiltersDay();   // ['url','title','state']
        $days = array_map(function($d){
            return [
                'url'   => $d['url']   ?? '#',
                'title' => $d['title'] ?? '',
                'state' => (!empty($d['state']) && $d['state'] === 'active') ? 'active' : null,
                'class' => 'day', // можно пометить классом, если нужно стилизовать отдельно
            ];
        }, $daysRaw);

        // 3) Единое правило активного:
        // - если задан $this->period (yesterday/today/tomorrow) → делаем активным соответствующий день
        // - иначе если задан $this->status → активируем соответствующий статус
        // - иначе активным будет 'All'
        $activeApplied = false;

        if (in_array($this->period, ['yesterday','today','tomorrow'], true)) {
            foreach ($status as &$s) $s['state'] = null; // снимаем актив со статусов
            foreach ($days as &$d) {
                $isActive = false;
                if ($this->period === 'yesterday' && stripos($d['title'], 'yesterday') !== false) $isActive = true;
                if ($this->period === 'today'     && stripos($d['title'], 'today')     !== false) $isActive = true;
                if ($this->period === 'tomorrow'  && stripos($d['title'], 'tomorrow')  !== false) $isActive = true;
                $d['state'] = $isActive ? 'active' : null;
                if ($isActive) $activeApplied = true;
            }
            unset($s, $d);
        } elseif (in_array($this->status, [null,'live','upcoming','finished'], true)) {
            // Обнулим дни
            foreach ($days as &$d) $d['state'] = null;
            unset($d);

            // Проставим актив на нужный статус
            foreach ($status as &$s) {
                $isActive = (
                    ($this->status === null      && $s['title'] === 'All') ||
                    ($this->status === 'live'    && $s['title'] === 'Live') ||
                    ($this->status === 'upcoming'&& $s['title'] === 'Upcoming') ||
                    ($this->status === 'finished'&& $s['title'] === 'Finished')
                );
                $s['state'] = $isActive ? 'active' : null;
                if ($isActive) $activeApplied = true;
            }
            unset($s);
        }

        // На всякий случай: если по каким-то причинам ничего не активировалось — активируем "All"
        if (!$activeApplied) {
            foreach ($status as &$s) {
                $s['state'] = ($s['title'] === 'All') ? 'active' : null;
            }
            foreach ($days as &$d) $d['state'] = null;
            unset($s, $d);
        }

        // 4) Порядок: сначала статусы, затем дни (можно поменять при желании)
        return array_merge($status, $days);
    }

    private function getFiltersCalendar(): array
    {
        if (!$this->baseUrl || !$this->sport || !$this->projectId) {
            return [];
        }

        $calendarIcon = sprintf('%s/images/content/calendar-icon.svg', SGWPLUGIN_URL_FRONT);
        $arrowIcon    = sprintf('%s/images/content/arrow-icon-up.svg', SGWPLUGIN_URL_FRONT);

        $today      = new \DateTimeImmutable('today');
        $todayStr   = $today->format('Y-m-d');
        $activeDate = $this->date ?: $todayStr;

        $dates = [];

        // -10 ... +10 дней относительно сегодня
        for ($i = -10; $i <= 10; $i++) {
            $dt = $today->modify(($i >= 0 ? '+' : '') . $i . ' days');
            $full = $dt->format('Y-m-d');

            // Формат: 25/09 TH
            $label = $dt->format('d/m') . ' ' . strtoupper($dt->format('D'));

            // В прошлое → finished, сегодня/будущее → upcoming
            $targetStatus = ($full >= $todayStr) ? 'upcoming' : 'finished';

            $dates[] = [
                'full_date'   => $full,
                'url'         => "/{$this->baseUrl}/{$targetStatus}/{$full}/",
                'active'      => ($full === $activeDate),
                'icon'        => $calendarIcon,
                'arrow_icon'  => $arrowIcon,
                'label'       => $label,
            ];
        }

        // Гарантируем, что хоть одна дата активна
        $hasActive = false;
        foreach ($dates as $d) {
            if (!empty($d['active'])) {
                $hasActive = true;
                break;
            }
        }

        if (!$hasActive) {
            foreach ($dates as &$d) {
                if ($d['full_date'] === $todayStr) {
                    $d['active'] = true;
                    break;
                }
            }
            unset($d);
        }

        return $dates;
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

    /** Собираем meta для "pin league" из события */
    private function buildPinMetaFromEvent(array $event): array
    {
        $leagueUrl = $event['leagueUrl'] ?? null;       // country slug
        $compUrl   = $event['competitionUrl'] ?? null;  // league slug
        $title     = $event['competition'] ?? null;
        $cid = null;

        if (!empty($event['competitionId'])) {
            $cid = (int)$event['competitionId'];
        } elseif (!empty($event['competition']['id'])) {
            $cid = (int)$event['competition']['id'];
        } elseif (!empty($event['competitionEntityId'])) {
            $cid = (int)$event['competitionEntityId'];
        }

        $slug = ($leagueUrl && $compUrl) ? ($leagueUrl . '/' . $compUrl) : null;
        $url  = ($leagueUrl && $compUrl && $this->baseUrl)
            ? ("/{$this->baseUrl}/{$leagueUrl}/{$compUrl}/")
            : null;

        return [
            'id'    => $cid ?: null,
            'slug'  => $slug,
            'title' => $title ?: null,
            'url'   => $url ?: null,
        ];
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
            'pinned_icon'    => sprintf('%s/images/content/pinn-icon.svg', SGWPLUGIN_URL_FRONT),
            'arrow_icon'    => sprintf('%s/images/content/arrow-icon-up.svg', SGWPLUGIN_URL_FRONT),
            'arrow_icon_white'    => sprintf('%s/images/content/arrow-icon-white.svg', SGWPLUGIN_URL_FRONT),
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
