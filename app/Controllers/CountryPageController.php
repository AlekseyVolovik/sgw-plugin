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

    // добавили — чтобы можно было синхронизировать поведение с лигой/каталогом
    private ?string $status = null; // 'live'|'upcoming'|'finished'|null
    private ?string $date   = null; // 'YYYY-mm-dd'|null

    public function __construct(array $params)
    {
        $this->sgw = SGWClient::getInstance();
        $this->projectId = Fields::get_general_project_id();
        $this->sport = Fields::get_general_sport();
        $this->countrySlug = $params['country'] ?? null;

        $this->status = $params['status'] ?? null;
        $this->date   = $params['date']   ?? null;
    }

    private function buildBreadcrumbs(): array
    {
        // Человеко-читаемое имя страны из slug
        $countryName = ucwords(str_replace('-', ' ', (string)$this->countrySlug));

        return [
            ['label' => 'Football',    'url' => '/football/'],
            ['label' => $countryName,  'url' => null], // текущая страница — без ссылки
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

    /**
     * Собрать список competitionId для текущей страны
     */
    private function getCompetitionIdsForCountry(): array
    {
        $ids = [];
        $response = $this->sgw->api->matchcentre->getMatchCentreCategories($this->projectId, $this->sport);
        if (empty($response['data'])) return $ids;

        foreach ($response['data'] as $item) {
            if ($item['entityType'] !== 'Competition') continue;
            $segments = $item['urlSegments'] ?? [];
            if (count($segments) < 2) continue;
            if ($segments[0] === $this->countrySlug) {
                $ids[] = (int)$item['entityId'];
            }
        }
        return $ids;
    }

    /**
     * Ключ события для дедупликации
     */
    private function makeEventKey(array $e): ?string
    {
        foreach (['id','eventId','externalId'] as $k) {
            if (!empty($e[$k])) return $k.':'.$e[$k];
        }
        if (!empty($e['urlSegment'])) return 'seg:'.trim($e['urlSegment'],'/');
        if (!empty($e['date']) && !empty($e['competitors'][0]['name']) && !empty($e['competitors'][1]['name'])) {
            return 'dt:'.date('Y-m-d', strtotime($e['date'])).'|'.
                Helpers::urlSlug($e['competitors'][0]['name']).'|' .
                Helpers::urlSlug($e['competitors'][1]['name']);
        }
        return null;
    }

    public function render(): ?string
    {
        if (!$this->projectId || !$this->sport || !$this->countrySlug) {
            return "<div>Invalid Country Page</div>";
        }

        // Мета
        $templateTitle = MetaBuilder::getTemplate('football_country', 'title');
        if ($templateTitle) {
            MetaBuilder::setTitle(MetaBuilder::buildMeta($templateTitle, [
                'country'   => ucfirst($this->countrySlug),
                'site_name' => get_bloginfo('name'),
            ]));
        }

        $templateDesc = MetaBuilder::getTemplate('football_country', 'description');
        if ($templateDesc) {
            MetaBuilder::setDescription(MetaBuilder::buildMeta($templateDesc, [
                'country'   => ucfirst($this->countrySlug),
                'site_name' => get_bloginfo('name'),
            ]));
        }

        $competitionIds = $this->getCompetitionIdsForCountry();
        if (empty($competitionIds)) {
            return "<div>No competitions for country</div>";
        }

        // какие статусы собираем
        $buckets = $this->status
            ? [
                $this->status => (
                    $this->status === 'live'
                        ? ['status' => 'live']
                        : ['period' => $this->status]
                )
            ]
            : [
                'live'     => ['status' => 'live'],
                'upcoming' => ['period' => 'upcoming'],
                'finished' => ['period' => 'finished'],
            ];

        $matchLists = ['live' => [], 'upcoming' => [], 'finished' => []];
        $seen = [];

        foreach ($buckets as $bucket => $baseParams) {
            foreach ($competitionIds as $competitionId) {
                $params = ['competitionId' => $competitionId] + $baseParams;

                if (!empty($this->date) && in_array($bucket, ['upcoming', 'finished'], true)) {
                    $params['fromDate'] = $this->date;
                    $params['toDate']   = $this->date;
                }

                $res = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, $params);
                if (empty($res['success']) || empty($res['data']['data'])) continue;

                foreach ($res['data']['data'] as $event) {
                    if (empty($event['competitionId'])) {
                        $event['competitionId'] = $competitionId;
                    }

                    $key = $this->makeEventKey($event);
                    if ($key && isset($seen[$key])) continue;
                    if ($key) $seen[$key] = true;

                    $matchLists[$bucket][] = $this->getEventCardContent($event, $bucket);
                }
            }
        }

        // сортировки
        $sortByDateAsc = function(array $a, array $b): int {
            $da = $a['date_iso'] ?? ($a['datetime']['attr'] ?? null) ?? null;
            $db = $b['date_iso'] ?? ($b['datetime']['attr'] ?? null) ?? null;
            return strcmp((string)$da, (string)$db);
        };
        $sortByDateDesc = function(array $a, array $b) use ($sortByDateAsc): int {
            return -$sortByDateAsc($a, $b);
        };

        if (!empty($matchLists['upcoming'])) {
            usort($matchLists['upcoming'], $sortByDateAsc);
        }
        if (!empty($matchLists['finished'])) {
            usort($matchLists['finished'], $sortByDateDesc);
        }

        // лимиты (как было)
        $matchLists['upcoming'] = array_slice($matchLists['upcoming'], 0, 15);
        $matchLists['finished'] = array_slice($matchLists['finished'], 0, 15);

        // активный таб
        $active = $this->status ?: 'live';
        if ($active === 'live' && empty($matchLists['live'])) {
            $active = !empty($matchLists['upcoming']) ? 'upcoming' : 'finished';
        }

        $breadcrumbs    = $this->buildBreadcrumbs();
        $bcArrowIcon    = sprintf('%s/images/content/arrow-icon-up.svg', SGWPLUGIN_URL_FRONT);
        $bcFootballIcon = sprintf('%s/images/content/football-icon.svg', SGWPLUGIN_URL_FRONT);

        // Иконки
        $pinnedIcon     = sprintf('%s/images/content/pinn-icon.svg', SGWPLUGIN_URL_FRONT);
        $arrowIcon      = sprintf('%s/images/content/arrow-icon-up.svg', SGWPLUGIN_URL_FRONT);
        $arrowIconWhite = sprintf('%s/images/content/arrow-icon-white.svg', SGWPLUGIN_URL_FRONT);

        // Структура filters — как в каталоге/лиге
        $filters = [
            'leagues_by_country' => $this->getGroupedLeaguesByCountry(),
        ];

        return Twig::render('pages/country/view.twig', [
            'countrySlug'             => $this->countrySlug,
            'filters'                 => $filters,
            'match_cards_by_status'   => $matchLists,
            'active_status'           => $active,
            'pinned_leagues'          => $this->getPinnedLeagues(),
            'breadcrumbs'             => $breadcrumbs,
            'pinned_icon'             => $pinnedIcon,
            'arrow_icon'              => $arrowIcon,
            'arrow_icon_white'        => $arrowIconWhite,
            'bc_arrow_icon'           => $bcArrowIcon,
            'bc_football_icon'        => $bcFootballIcon,
        ]);
    }
}
