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

    private ?string $status = null;
    private ?string $date   = null;


    public function __construct(array $params)
    {
        $this->sgw = SGWClient::getInstance();
        $this->projectId = Fields::get_general_project_id();
        $this->sport = Fields::get_general_sport();
        $this->leagueSlug = $params['league'] ?? null;
        $this->countrySlug = $params['country'] ?? null;

        $this->status = $params['status'] ?? null; // 'live'|'upcoming'|'finished'|null
        $this->date   = $params['date']   ?? null; // 'YYYY-mm-dd' или null
    }

    private function buildBreadcrumbs(array $league): array
    {
        // Названия
        $leagueName  = $league['name'] ?? null;
        // Человекочитаемое имя страны из slug
        $countryName = $league['urlSegments'][0] ?? $this->countrySlug ?? '';
        $countryName = ucwords(str_replace('-', ' ', (string)$countryName));

        // Ссылки
        $countrySlug = $league['urlSegments'][0] ?? $this->countrySlug ?? null;

        $items = [
            ['label' => 'Football',   'url' => '/football/'],
            ['label' => $countryName, 'url' => $countrySlug ? "/football/{$countrySlug}/" : null],
            ['label' => $leagueName,  'url' => null], // финальная крошка без ссылки
        ];

        return $items;
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
        if (!$this->projectId || !$this->sport) return ['live'=>[], 'upcoming'=>[], 'finished'=>[]];

        // Базовые билдеры параметров
        $buildParams = function(string $mode) use ($competitionId): array {
            $p = ['competitionId' => $competitionId];
            if ($mode === 'live') {
                $p['status'] = 'live';
            } elseif ($mode === 'upcoming') {
                $p['period'] = 'upcoming';
                if (!empty($this->date)) $p['fromDate'] = $p['toDate'] = $this->date;
            } elseif ($mode === 'finished') {
                $p['period'] = 'finished';
                if (!empty($this->date)) $p['fromDate'] = $p['toDate'] = $this->date;
            }
            return $p;
        };

        $fetch = function(array $params): array {
            $r = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, $params);
            return (!empty($r['success']) && !empty($r['data']['data'])) ? $r['data']['data'] : [];
        };

        $live     = $fetch($buildParams('live'));
        $upcoming = $fetch($buildParams('upcoming'));
        $finished = $fetch($buildParams('finished'));

        // Единая сортировка
        usort($upcoming, fn($a,$b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));           // ASC
        usort($finished, fn($a,$b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));           // DESC

        return [
            'live'     => $live,
            'upcoming' => $upcoming,
            'finished' => $finished,
        ];
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
        if (empty($event['competitionId']) && !empty($event['competition']['id'])) {
            $event['competitionId'] = (int)$event['competition']['id'];
        }
        return \SGWPlugin\Classes\MatchCardFactory::build($event, $modify);
    }

    private function transformEventsToMatchCards(array $events, string $modify): array
    {
        return array_map(function($e) use ($modify) {
            if (empty($e['competitionId']) && !empty($e['competition']['id'])) {
                $e['competitionId'] = (int)$e['competition']['id'];
            }
            return \SGWPlugin\Classes\MatchCardFactory::build($e, $modify);
        }, $events);
    }

    public function render(): ?string
    {
        $league = $this->getLeagueData();
        if (!$league) return "<div>League Not Found</div>";

        // Title
        $titleTemplate = MetaBuilder::getTemplate('league', 'title');
        if ($titleTemplate) {
            $title = MetaBuilder::buildMeta($titleTemplate, [
                'league'    => $league['name'],
                'site_name' => get_bloginfo('name'),
            ]);
            MetaBuilder::setTitle($title);
        }

        // Description
        $descTemplate = MetaBuilder::getTemplate('league', 'description');
        if ($descTemplate) {
            $description = MetaBuilder::buildMeta($descTemplate, [
                'league'    => $league['name'],
                'site_name' => get_bloginfo('name'),
                'country'   => ucfirst($this->countrySlug),
            ]);
            MetaBuilder::setDescription($description);
        }

        // Матчи по статусам
        $events = $this->getLeagueEvents((int)$league['entityId']);

        $matchCardsByStatus = [
            'live'     => array_map(fn($e) => \SGWPlugin\Classes\MatchCardFactory::build($e, 'live'),     $events['live']),
            'upcoming' => array_map(fn($e) => \SGWPlugin\Classes\MatchCardFactory::build($e, 'upcoming'), $events['upcoming']),
            'finished' => array_map(fn($e) => \SGWPlugin\Classes\MatchCardFactory::build($e, 'finished'), $events['finished']),
        ];

        // Активный таб
        $active = $this->status ?: 'live';
        if ($active === 'live' && empty($matchCardsByStatus['live'])) {
            $active = !empty($matchCardsByStatus['upcoming']) ? 'upcoming' : 'finished';
        }

        $breadcrumbs     = $this->buildBreadcrumbs($league);
        $bcArrowIcon     = sprintf('%s/images/content/arrow-icon-up.svg', SGWPLUGIN_URL_FRONT);
        $bcFootballIcon  = sprintf('%s/images/content/football-icon.svg', SGWPLUGIN_URL_FRONT);

        // Иконки
        $pinnedIcon      = sprintf('%s/images/content/pinn-icon.svg', SGWPLUGIN_URL_FRONT);
        $arrowIcon       = sprintf('%s/images/content/arrow-icon-up.svg', SGWPLUGIN_URL_FRONT);
        $arrowIconWhite  = sprintf('%s/images/content/arrow-icon-white.svg', SGWPLUGIN_URL_FRONT);

        // Структура filters — как в CatalogController
        $filters = [
            'leagues_by_country' => $this->getGroupedLeaguesByCountry(),
            // при желании можно добавить calendar/combined,
            // но для сайдбара нужны только страны
        ];

        return Twig::render('pages/league/view.twig', [
            'countrySlug'              => $this->countrySlug,
            'leagueName'               => $league['name'],
            'filters'                  => $filters,
            'match_cards_by_status'    => $matchCardsByStatus,
            'pinned_leagues'           => $this->getPinnedLeagues(),
            'active_status'            => $active,
            'breadcrumbs'              => $breadcrumbs,
            'pinned_icon'              => $pinnedIcon,
            'arrow_icon'               => $arrowIcon,
            'arrow_icon_white'         => $arrowIconWhite,
            'bc_arrow_icon'            => $bcArrowIcon,
            'bc_football_icon'         => $bcFootballIcon,
        ]);
    }

}
