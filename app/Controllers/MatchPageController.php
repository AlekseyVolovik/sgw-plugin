<?php declare(strict_types=1);

namespace SGWPlugin\Controllers;

use SGWClient;
use SGWPlugin\Classes\Fields;
use SGWPlugin\Classes\Twig;
use SGWPlugin\Classes\MetaBuilder;
use SGWPlugin\Classes\Helpers;

/**
 * Контроллер страницы матча.
 */
class MatchPageController
{

    /** Клиент SGW для API-вызовов */
    private SGWClient $sgw;

    /** Идентификатор проекта (из настроек сайта) */
    private ?int $projectId;

    /** Вид спорта для MatchCentre/Events */
    private ?string $sport;

    /** Текущий URL-слуг матча */
    private ?string $slug;

    private ?string $baseUrl;

    /**
     * Глубина поиска завершённых матчей при разрешении события.
     * На проде можно уменьшить (например, до 2–3 лет).
     */
    private const FINISHED_LOOKBACK_YEARS = 10;

    /**
     * Окна поиска по завершённым матчам (по сегменту).
     * Ступенчатый подход: ±7 дней, затем ±60, затем широкий захват.
     */
    private const FINISHED_WINDOWS = [
        ['-7 days',  '+7 days'],
        ['-60 days', '+60 days'],
        ['-10 years','0 days'],
    ];

    /** Кэш имён команд по ID — чтобы чинить пропажи в previousEvents */
    /** @var array<int,string> */
    private array $teamNameCache = [];

    /** Кэш деталей Events API по eventId — для дозаполнения previousEvents */
    /** @var array<int, array> */
    private array $eventDetailsCache = [];

    /**
     * Конструктор: инициализация клиента и общих параметров.
     */
    public function __construct(array $params)
    {
        $this->sgw       = SGWClient::getInstance();
        $this->projectId = Fields::get_general_project_id();
        $this->sport     = Fields::get_general_sport();
        $this->slug      = $params['slug'] ?? null;
        $this->baseUrl   = Fields::get_general_url_catalog_page() ?: 'football';
    }

    /* =========================
     *      PUBLIC ENTRYPOINT
     * ========================= */

    /**
     * Рендер страницы матча.
     *
     * Шаги:
     *  1) Разобрать slug и найти событие.
     *  2) Собрать базовую VM + meta-теги.
     *  3) Вытащить MatchCentre event (первый осмысленный ответ).
     *  4) Построить блоки: счёт/статус, составы, форма, последние матчи.
     *  5) Рендер Twig.
     */
    public function render(): string
    {
        $parts = $this->parseSlug();
        if (!$parts) return "<div>Invalid match URL</div>";

        // 1) Поиск события
        $event = $this->resolveEventByParts($parts);
        if (!$event) return "<div>Match not found</div>";

        // 2) Канонический URL и мета
        $canonical = \SGWPlugin\Classes\MatchUrl::build($event);
        $meta = $this->applyMatchMeta($event); // сразу проставляет Title/Description

        // 3) Базовая VM + info из Events API
        $match   = $this->buildViewModel($event);
        $eventId = (int)($match['id'] ?? 0);
        $details = $eventId ? $this->fetchEventDetails($eventId) : [];
        $match['info'] = $this->extractMatchInfo($details, $match);

        $breadcrumbs    = $this->buildBreadcrumbs($event, $match);
        $bcArrowIcon    = sprintf('%s/images/content/arrow-icon-up.svg', SGWPLUGIN_URL_FRONT);
        $bcFootballIcon = sprintf('%s/images/content/football-icon.svg', SGWPLUGIN_URL_FRONT);

        // 4) MatchCentre (сырое событие — только для построения блоков)
        $mc        = $this->fetchMatchCentreEventRaw($event);
        $mcPayload = $mc['response'] ?? [];
        $mcEvent   = is_array($mcPayload['event'] ?? null) ? $mcPayload['event'] : [];

        // PreviousEvents/форма
        $prev = $mcEvent['details']['previousEvents'] ?? [];
        $t1Id = $event['competitors'][0]['id']   ?? null;
        $t2Id = $event['competitors'][1]['id']   ?? null;
        $t1Nm = $event['competitors'][0]['name'] ?? 'Team 1';
        $t2Nm = $event['competitors'][1]['name'] ?? 'Team 2';

        // прогреем имена на время запроса (не межзапросный кэш)
        $this->cacheTeamName($t1Id, $t1Nm);
        $this->cacheTeamName($t2Id, $t2Nm);
        foreach ($prev as $pe) {
            $h = $pe['teams']['home'] ?? null;
            $a = $pe['teams']['away'] ?? null;
            $this->cacheTeamName($h['id'] ?? null, $h['name'] ?? null);
            $this->cacheTeamName($a['id'] ?? null, $a['name'] ?? null);
        }

        // БЕЗ КЭША: всегда пересчитываем previousGroups
        $previousGroups = $this->splitPreviousEvents($prev, $t1Id, $t2Id, $t1Nm, $t2Nm);

        $scoreStatus = $this->buildScoreAndStatus($mcEvent);
        $lineups     = $this->buildLineupsBlock($mcEvent);
        $formTable   = $this->buildTeamForm($prev, $mcEvent['competitors'] ?? []);
        $hlHalves    = $this->splitHighlightsByHalves($mcEvent);

        // Общие иконки
        $pinnedIcon     = sprintf('%s/images/content/pinn-icon.svg', SGWPLUGIN_URL_FRONT);
        $arrowIcon      = sprintf('%s/images/content/arrow-icon-up.svg', SGWPLUGIN_URL_FRONT);
        $arrowIconWhite = sprintf('%s/images/content/arrow-icon-white.svg', SGWPLUGIN_URL_FRONT);

        $iconGoal   = sprintf('%s/images/content/icon-goal.svg', SGWPLUGIN_URL_FRONT);
        $iconYellow = sprintf('%s/images/content/icon-yellow-card.svg', SGWPLUGIN_URL_FRONT);
        $iconRed    = sprintf('%s/images/content/icon-red-card.svg', SGWPLUGIN_URL_FRONT);
        $iconSub    = sprintf('%s/images/content/substitution.svg', SGWPLUGIN_URL_FRONT);

        // Формат filters как на каталоге/стране/лиге
        $filters = [
            'leagues_by_country' => $this->getGroupedLeaguesByCountry(),
        ];

        $html = Twig::render('pages/match/view.twig', [
            'match'                    => $match,
            'breadcrumbs'              => $breadcrumbs,
            'canonical'                => $canonical,
            'mc_args'                  => $mc['args'] ?? [],
            'mc_payload'               => $mc['response'] ?? [],
            'mc_event'                 => $mcEvent,
            'mc_score'                 => $scoreStatus['breakdown'] ?? [],
            'mc_status'                => $scoreStatus['status'] ?? [],
            'mc_lineups'               => $lineups ?? [],
            'mc_form'                  => $formTable ?? [],
            'prev_groups'              => $previousGroups,
            'mc_highlights_halves'     => $hlHalves,
            'bc_arrow_icon'            => $bcArrowIcon,
            'bc_football_icon'         => $bcFootballIcon,

            'filters'                  => $filters,
            'pinned_leagues'           => $this->getPinnedLeagues(),

            'pinned_icon'              => $pinnedIcon,
            'arrow_icon'               => $arrowIcon,
            'arrow_icon_white'         => $arrowIconWhite,
            'icon_goal'                => $iconGoal,
            'icon_yellow'              => $iconYellow,
            'icon_red'                 => $iconRed,
            'icon_sub'                 => $iconSub,
        ]);

        return $html;
    }

    private function getGroupedLeaguesByCountry(): array
    {
        $result = [];

        if (!$this->projectId || !$this->sport) {
            return $result;
        }

        $response = $this->sgw->api->matchcentre->getMatchCentreCategories($this->projectId, $this->sport);

        if (!isset($response['data'])) {
            return $result;
        }

        foreach ($response['data'] as $item) {
            if (($item['entityType'] ?? '') !== 'Competition') {
            continue;
            }

            $segments = $item['urlSegments'] ?? [];
            if (count($segments) < 2) {
                continue;
            }

            $country = $segments[0];
            $league  = $segments[1];

            $result[$country][] = [
                'title' => $item['name'],
                'url'   => sprintf('/%s/%s/%s/', $this->baseUrl, $country, $league),
            ];
        }

        return $result;
    }

    private function getPinnedLeagues(): array
    {
        $result = [];
        $ids    = Helpers::getPinnedLeagueIds();

        if (!$this->projectId || !$this->sport || empty($ids)) {
            return $result;
        }

        $response = $this->sgw->api->matchcentre->getMatchCentreCategories($this->projectId, $this->sport);

        if (!isset($response['data'])) {
            return $result;
        }

        foreach ($response['data'] as $item) {
            if (($item['entityType'] ?? '') !== 'Competition') {
                continue;
            }
            if (!in_array($item['entityId'], $ids, true)) {
                continue;
            }

            $segments = $item['urlSegments'] ?? [];
            if (count($segments) < 2) {
                continue;
            }

            $country = $segments[0];
            $league  = $segments[1];

            $result[] = [
                'title' => $item['name'],
                'url'   => sprintf('/%s/%s/%s/', $this->baseUrl, $country, $league),
            ];
        }

        return $result;
    }

    /**
     * Быстрый резолв события по распарсенному slug (ровно то, что было в switch-case).
     */
    private function resolveEventByParts(array $parts): ?array
    {
        $cidFromSlug = $parts['cid'] ?? null;

        switch ($parts['mode']) {
            case 'segment+date':
                $event = $this->findBySegmentOnDate($parts['segment'], $parts['id'] ?? null, $parts['date'], $cidFromSlug);
                if (!$event && !empty($parts['id'])) $event = $this->findById((int)$parts['id'], $parts['date'], $cidFromSlug);
                return $event;

            case 'segment':
                if (!empty($parts['id'])) {
                    $event = $this->findById((int)$parts['id'], null, $cidFromSlug);
                    if ($event) return $event;
                }
                return $this->findBySegment($parts['segment'], $parts['id'] ?? null, $cidFromSlug);

            case 'teams+id':
                return $this->findById((int)$parts['id'], null, $cidFromSlug);

            case 'teams+date':
                return $this->findByTeamsOnDate($parts['team1'], $parts['team2'], $parts['date'], $cidFromSlug);
        }
        return null;
    }

    /** Хлебные крошки: Football → Country → League */
    private function buildBreadcrumbs(array $event, array $match): array
    {
        $items = [
            ['label' => 'Football', 'url' => '/football/'],
        ];

        // Названия
        $countryName = $match['league']['country'] ?? ($event['league'] ?? null);
        $leagueName  = $match['league']['name']    ?? ($event['competition'] ?? null);

        // Слаги для ссылок
        $countrySlug = $event['leagueUrl']      ?? null; // страна
        $leagueSlug  = $event['competitionUrl'] ?? null; // лига

        // Страна
        if ($countryName) {
            $countryUrl = $countrySlug ? "/football/{$countrySlug}/" : null;
            $items[] = [
                'label' => $countryName,
                'url'   => $countryUrl,
            ];
        }

        // Лига (делаем ссылкой, если знаем полный путь)
        if ($leagueName) {
            $leagueUrl = $match['league']['url'] ?? (
                ($countrySlug && $leagueSlug) ? "/football/{$countrySlug}/{$leagueSlug}/" : null
            );

            $items[] = [
                'label' => $leagueName,
                'url'   => $leagueUrl,
            ];
        }

        return $items;
    }

    /**
     * Ставит Title/Description для матча и возвращает их (для кэша).
     * Возвращает ['title'=>string, 'description'=>string].
     */
    private function applyMatchMeta(array $event): array
    {
        $team1    = $event['competitors'][0]['name'] ?? 'Team 1';
        $team2    = $event['competitors'][1]['name'] ?? 'Team 2';
        $siteName = get_bloginfo('name');
        $dateObj  = !empty($event['date']) ? new \DateTime($event['date']) : null;
        $date     = $dateObj ? $dateObj->format('F j, Y') : 'TBD';

        // Title
        $titleTemplate = MetaBuilder::getTemplate('match', 'title');
        $title = $titleTemplate
            ? MetaBuilder::buildMeta($titleTemplate, [
                'team1'     => $team1,
                'team2'     => $team2,
                'site_name' => $siteName,
            ])
            : sprintf('%s vs %s – Match Preview, Kick-off & Stats | %s', $team1, $team2, $siteName);

        MetaBuilder::setTitle($title);

        // Description
        $descTemplate = MetaBuilder::getTemplate('match', 'description');
        $description = $descTemplate
            ? MetaBuilder::buildMeta($descTemplate, [
                'team1'     => $team1,
                'team2'     => $team2,
                'date'      => $date,
                'site_name' => $siteName,
            ])
            : sprintf(
                'Get full match info for %s vs %s on %s: score, kick-off time, head-to-head stats, recent form, and more. Live updates on %s.',
                $team1, $team2, $date, $siteName
            );

        MetaBuilder::setDescription($description);

        return ['title' => $title, 'description' => $description];
    }

    /* =========================
     *     URL / SLUG HELPERS
     * ========================= */

    /**
     * Разбор slug на режимы:
     *  - segment+date, segment, teams+id, teams+date (+ опциональный -c{cid})
     * Возвращает структуру с mode и полями для поиска.
     */
    private function parseSlug(): ?array
    {
        if (!$this->slug) return null;
        $slug = rtrim(urldecode($this->slug), '/');

        if (preg_match('~^(.+-\d+)-(\d{4}-\d{2}-\d{2})(?:-c(\d+))?$~', $slug, $m)) {
            return ['mode'=>'segment+date','segment'=>$m[1],'id'=>$this->extractIdFromSuffix($m[1]),'date'=>$m[2],'cid'=>isset($m[3])?(int)$m[3]:null];
        }
        if (preg_match('~^(.+)-vs-(.+)-(\d+)(?:-c(\d+))?$~', $slug, $m)) {
            return ['mode'=>'teams+id','team1'=>str_replace('-', ' ', $m[1]),'team2'=>str_replace('-', ' ', $m[2]),'id'=>(int)$m[3],'cid'=>isset($m[4])?(int)$m[4]:null];
        }
        if (preg_match('~^(.+)-vs-(.+)-(\d{4}-\d{2}-\d{2})(?:-c(\d+))?$~', $slug, $m)) {
            return ['mode'=>'teams+date','team1'=>str_replace('-', ' ', $m[1]),'team2'=>str_replace('-', ' ', $m[2]),'date'=>$m[3],'cid'=>isset($m[4])?(int)$m[4]:null];
        }
        if (preg_match('~^((?:(?!-vs-).)+)-(\d+)(?:-c(\d+))?$~', $slug, $m)) {
            return ['mode'=>'segment','segment'=>$m[1].'-'.$m[2],'id'=>(int)$m[2],'cid'=>isset($m[3])?(int)$m[3]:null];
        }
        return null;
    }

    /** Достаёт числовой ID из хвоста сегмента вида "...-12345". */
    private function extractIdFromSuffix(string $segment): ?int
    {
        return (preg_match('~-(\d+)$~', $segment, $m)) ? (int)$m[1] : null;
    }

    /** Убирает технический хвост "-c{cid}" для корректного сравнения сегментов. */
    private function normalizeSegment(string $segment): string
    {
        $segment = trim($segment, '/');
        return preg_replace('~\-c\d+\/?$~', '', $segment);
    }

    /** Сравнение нормализованных сегментов события и URL. */
    private function sameSegment(array $e, string $segment): bool
    {
        $segGiven = $this->normalizeSegment($segment);
        $segEvent = $this->normalizeSegment((string)($e['urlSegment'] ?? ''));
        return $segEvent !== '' && $segEvent === $segGiven;
    }

    /* =========================
     *        DATA ACCESS
     * ========================= */

    /**
     * Универсальная обёртка для matchcentre->getMatchCentreEvents()
     * Возвращает массив событий или пустой массив.
     */
    private function fetch(array $params): array
    {
        $r = $this->sgw->api->matchcentre->getMatchCentreEvents($this->projectId, $this->sport, $params);
        return (!empty($r['success']) && !empty($r['data']['data'])) ? $r['data']['data'] : [];
    }

    /**
     * Events API: детали события по ID (для match info и дозаполнения previousEvents).
     */
    private function fetchEventDetails(int $eventId): array
    {
        if ($eventId <= 0) return [];
        $res = $this->sgw->api->events->getEventById($eventId);
        return (isset($res['data']) && is_array($res['data'])) ? $res['data'] : (is_array($res) ? $res : []);
    }

    /**
     * Построить набор кандидатов для MatchCentre (event/league/competition/section)
     * и перебирать их, пока не получим «осмысленный» payload.
     * Возвращает:
     *  - response: полезная нагрузка (обычно содержит ['event'=>...]).
     */
    private function fetchMatchCentreEventRaw(array $event): array
    {
        // Берём только «надежные» идентификаторы и только section=summary (1 вызов)
        $candidates = array_values(array_filter(array_unique([
            $event['contentKey'] ?? null,
            isset($event['id']) ? (string)$event['id'] : null,
            isset($event['eventId']) ? (string)$event['eventId'] : null,
            $event['urlSegment'] ?? null,
        ])));

        if (!$candidates) {
            return ['tried'=>[], 'picked'=>null, 'response'=>[]];
        }

        foreach ($candidates as $evt) {
            $res = $this->sgw->api->matchcentre->getMatchCentreEvent(
                $this->projectId, $this->sport, (string)$evt, ['section' => 'summary']
            );
            $data = (isset($res['data']) && is_array($res['data'])) ? $res['data'] : (is_array($res) ? $res : []);
            if (!empty($data)) {
                return [
                    'tried'    => [['event'=>$evt,'section'=>'summary']],
                    'picked'   => ['event'=>$evt,'section'=>'summary'],
                    'response' => $data,
                ];
            }
        }

        return ['tried'=>[], 'picked'=>null, 'response'=>[]];
    }

    /* =========================
     *       SEARCH HELPERS
     * ========================= */

    /** Добавляет competitionId в параметры запроса, если он присутствует в slug. */
    private function withCid(array $params, ?int $cid): array
    {
        if ($cid) $params['competitionId'] = $cid;
        return $params;
    }

    /** Проверка совпадения ID события (id/eventId/externalId). */
    private function matchesEventId(array $e, int $id): bool
    {
        foreach (['id','eventId','externalId'] as $k) {
            if (isset($e[$k]) && is_numeric($e[$k]) && (int)$e[$k] === $id) return true;
        }
        return false;
    }

    /**
     * Найти событие по ID (с учётом даты/competitionId).
     * Порядок: конкретная дата (finished/upcoming) → live/today/upcoming → широкий finished-диапазон.
     */
    private function findById(int $id, ?string $date = null, ?int $cid = null): ?array
    {
        if ($date) {
            foreach ((['finished','upcoming']) as $period) {
                $data = $this->fetch($this->withCid(['period'=>$period,'fromDate'=>$date,'toDate'=>$date], $cid));
                foreach ($data as $e) if ($this->matchesEventId($e, $id)) return $e;
            }
        }
        foreach ([['status'=>'live'], ['period'=>'today'], ['period'=>'upcoming']] as $p) {
            $data = $this->fetch($this->withCid($p, $cid));
            foreach ($data as $e) if ($this->matchesEventId($e, $id)) return $e;
        }
        $data = $this->fetch($this->withCid([
            'period'=>'finished',
            'fromDate'=>date('Y-m-d', strtotime('-'.self::FINISHED_LOOKBACK_YEARS.' years')),
            'toDate'=>date('Y-m-d'),
        ], $cid));
        foreach ($data as $e) if ($this->matchesEventId($e, $id)) return $e;
        return null;
    }

    /**
     * Найти событие по сегменту на конкретную дату (с учётом опционального ID/competitionId).
     */
    private function findBySegmentOnDate(string $segment, ?int $id, string $date, ?int $cid = null): ?array
    {
        foreach (['finished','upcoming'] as $period) {
            $data = $this->fetch($this->withCid(['period'=>$period,'fromDate'=>$date,'toDate'=>$date], $cid));
            foreach ($data as $e) if (($id && $this->matchesEventId($e, $id)) || $this->sameSegment($e, $segment)) return $e;
        }
        $data = $this->fetch($this->withCid(['status'=>'live'], $cid));
        foreach ($data as $e) if (($id && $this->matchesEventId($e, $id)) || $this->sameSegment($e, $segment)) return $e;
        return null;
    }

    /**
     * Найти событие по сегменту (без даты).
     * Порядок: live/today/upcoming → оконные finished-поиски.
     */
    private function findBySegment(string $segment, ?int $id, ?int $cid = null): ?array
    {
        foreach ([['status'=>'live'], ['period'=>'today'], ['period'=>'upcoming']] as $p) {
            $data = $this->fetch($this->withCid($p, $cid));
            foreach ($data as $e) if (($id && $this->matchesEventId($e, $id)) || $this->sameSegment($e, $segment)) return $e;
        }
        foreach (self::FINISHED_WINDOWS as [$from, $to]) {
            $data = $this->fetch($this->withCid([
                'period'=>'finished',
                'fromDate'=>date('Y-m-d', strtotime($from)),
                'toDate'=>date('Y-m-d', strtotime($to)),
            ], $cid));
            foreach ($data as $e) if (($id && $this->matchesEventId($e, $id)) || $this->sameSegment($e, $segment)) return $e;
        }
        return null;
    }

    /**
     * Найти событие по парам названий команд и дате (возможна перестановка home/away).
     */
    private function findByTeamsOnDate(string $team1, string $team2, string $date, ?int $cid = null): ?array
    {
        $s1 = Helpers::urlSlug($team1);
        $s2 = Helpers::urlSlug($team2);

        $collect = function(string $period) use ($date, $cid): array {
            return $this->fetch($this->withCid(['period'=>$period, 'fromDate'=>$date, 'toDate'=>$date], $cid));
        };

        $candidates = array_merge($collect('upcoming'), $collect('finished'));
        foreach ($candidates as $e) {
            $a = Helpers::urlSlug($e['competitors'][0]['name'] ?? '');
            $b = Helpers::urlSlug($e['competitors'][1]['name'] ?? '');
            if (($a === $s1 && $b === $s2) || ($a === $s2 && $b === $s1)) return $e;
        }
        return null;
    }

    /* =========================
     *       VIEW BUILDERS
     * ========================= */

    /**
     * Базовая view-model:
     *  - команды (id, имя, логотип/флаг, счёт, qualifier, победитель),
     *  - дата/время (удобные + сырые поля),
     *  - лига/турнир + URL,
     *  - локация,
     *  - competitionId.
     */
    private function buildViewModel(array $e): array
    {
        $defaultLogo = SGWPLUGIN_URL_FRONT . '/images/content/team-placeholder.png';
        $teams = [];
        for ($i = 0; $i < 2; $i++) {
            $t    = $e['competitors'][$i] ?? [];
            $abbr = $t['abbreviation'] ?? '';
            $logo = Helpers::getFlag($abbr) ?: $defaultLogo;
            $teams[] = [
                'id'        => $t['id'] ?? null,
                'name'      => $t['name'] ?? "Team ".($i+1),
                'abbr'      => $abbr ?: null,
                'urlSegment'=> $t['urlSegment'] ?? null,
                'logo'      => $logo,
                'score'     => $t['score'] ?? null,
                'qualifier' => $t['qualifier'] ?? null,
                'isWinner'  => !empty($t['isWinner']),
            ];
        }

        $dt = ['display'=>null,'attr'=>null,'date'=>null,'time'=>null,'rawDate'=>null,'rawTime'=>null];
        if (!empty($e['date'])) {
            $parts = Helpers::convertIsoDateTime($e['date']); // ['date'=>'Y-m-d', 'time'=>'H:i:s']
            $dt = [
                'attr'    => $e['date'],
                'date'    => date('M j, Y', strtotime($parts['date'])),
                'time'    => Helpers::convertTimeTo12hFormat($parts['time']),
                'rawDate' => $parts['date'],
                'rawTime' => $parts['time'],
            ];
            $dt['display'] = trim($dt['date'].' '.$dt['time']);
        }

        $leagueUrl = null;
        if (!empty($e['leagueUrl']) && !empty($e['competitionUrl'])) {
            $leagueUrl = "/football/{$e['leagueUrl']}/{$e['competitionUrl']}/";
        }

        $venueParts = [];
        if (!empty($e['place'])) $venueParts[] = $e['place'];
        if (!empty($e['city']))  $venueParts[] = $e['city'];
        $venue = implode(', ', $venueParts);

        return [
            'id'            => $e['eventId'] ?? ($e['id'] ?? null),
            'urlSegment'    => $e['urlSegment'] ?? null,
            'status'        => $e['status'] ?? null,
            'status_note'   => $e['description'] ?? null,
            'datetime'      => $dt,
            'league'        => [
                'country' => $e['league'] ?? null,
                'name'    => $e['competition'] ?? null,
                'url'     => $leagueUrl,
            ],
            'venue'         => $venue,
            'teams'         => $teams,
            'competitionId' => $e['competitionId'] ?? null,
        ];
    }

    /**
     * Match info (для правой/верхней панели):
     *  статус, дата, время начала, заметка (description), elapsed,
     *  лига/турнир/раунд, место (стадион/город).
     *  Никаких «сырых» массивов/odds — только готовые значения.
     */
    private function extractMatchInfo(array $details, array $vmBase): array
    {
        $items = [];
        $push = function (string $label, $value) use (&$items) {
            if ($value === null) return;
            if (is_string($value)) $value = trim($value);
            if ($value === '' || $value === []) return;
            $items[] = ['label' => $label, 'value' => $value];
        };

        $status = $details['status'] ?? ($vmBase['status'] ?? null);
        if ($status) $push('Status', ucfirst(strtolower((string)$status)));

        if (!empty($vmBase['datetime']['rawDate'])) {
            $push('Date', date('j F Y', strtotime($vmBase['datetime']['rawDate'])));
        }
        if (!empty($vmBase['datetime']['attr'])) {
            $push('Kick-off', date('H:i', strtotime($vmBase['datetime']['attr'])));
        } elseif (!empty($vmBase['datetime']['rawTime'])) {
            $push('Kick-off', Helpers::convertTimeTo12hFormat($vmBase['datetime']['rawTime']));
        }

        if (!empty($details['description'])) $push('Note', $details['description']);
        if (isset($details['elapsed'])) {
            $push('Elapsed', is_numeric($details['elapsed']) ? ($details['elapsed']."'") : $details['elapsed']);
        }

        $push('Country', $details['league'] ?? null);
        $push('League', $details['competition'] ?? null);
        $push('Round', $details['round'] ?? null);

        $place = $details['place'] ?? null;
        $city  = $details['city']  ?? null;
        $push('Venue', $place && $city ? ($place.', '.$city) : ($place ?? $city));

        return ['items' => $items];
    }

    /**
     * Статус и счёт (включая HT/FT/ET/PEN) из MatchCentre.
     */
    private function buildScoreAndStatus(array $mcEvent): array
    {
        $d  = $mcEvent['details'] ?? [];
        $sb = $d['scoreBreakdown'] ?? [];

        $row = function(string $k) use ($sb): array {
            $x = $sb[$k] ?? [];
            return ['home' => $x['home'] ?? null, 'away' => $x['away'] ?? null];
        };

        $st  = $d['detailedStatus'] ?? [];
        $el  = $st['elapsed'] ?? ($mcEvent['elapsed'] ?? null);
        $elapsed = is_numeric($el) ? ((int)$el)."’" : ($el ?: null);

        return [
            'breakdown' => [
                'halftime'  => $row('halftime'),
                'fulltime'  => $row('fulltime'),
                'extratime' => $row('extratime'),
                'penalty'   => $row('penalty'),
            ],
            'status' => [
                'long'   => $st['long']  ?? null,
                'short'  => $st['short'] ?? ($mcEvent['description'] ?? null),
                'elapsed'=> $elapsed,
            ],
        ];
    }

    /**
     * Составы (формации, цвета, тренер, игроки) из MatchCentre.
     */
    private function buildLineupsBlock(array $mcEvent): array
    {
        $d        = $mcEvent['details'] ?? [];
        $lineups  = $d['lineups'] ?? ['home'=>[], 'away'=>[]];
        $players  = $d['players'] ?? ['home'=>[], 'away'=>[]];

        $team = function(string $side) use ($lineups, $players): array {
            $lu = $lineups[$side] ?? [];
            return [
                'formation' => $lu['formation'] ?? null,
                'colors'    => $lu['colors']    ?? [],
                'coach'     => $lu['coach']     ?? null,
                'players'   => $players[$side]  ?? [],
            ];
        };

        return ['home'=>$team('home'), 'away'=>$team('away')];
    }

    /**
     * Таблица формы по последним 5 матчам из previousEvents.
     * Возвращает строки по обеим командам с MP/W/D/L/GF:GA/GD/PTS и лентой FORM.
     */
    private function buildTeamForm(array $previousEvents, array $competitors): array
    {
        $calc = function(int $teamId) use ($previousEvents): array {
            $list = array_values(array_filter($previousEvents, function($pe) use ($teamId) {
                $h = $pe['teams']['home']['id'] ?? null;
                $a = $pe['teams']['away']['id'] ?? null;
                return ($h === $teamId) || ($a === $teamId);
            }));
            usort($list, fn($a,$b) => strcmp($b['dateTime'] ?? '', $a['dateTime'] ?? ''));

            $take   = array_slice($list, 0, 5);
            $stats  = ['MP'=>0,'W'=>0,'D'=>0,'L'=>0,'GF'=>0,'GA'=>0,'PTS'=>0,'FORM'=>[]];

            foreach ($take as $pe) {
                $hId = $pe['teams']['home']['id'] ?? null;
                $aId = $pe['teams']['away']['id'] ?? null;
                $hs  = (int)($pe['score']['home'] ?? 0);
                $as  = (int)($pe['score']['away'] ?? 0);

                if ($hId === $teamId)      { $gf = $hs; $ga = $as; }
                elseif ($aId === $teamId)  { $gf = $as; $ga = $hs; }
                else continue;

                $stats['MP']++;
                $stats['GF'] += $gf; $stats['GA'] += $ga;

                if ($gf > $ga) { $stats['W']++; $stats['PTS'] += 3; $stats['FORM'][] = 'W'; }
                elseif ($gf == $ga) { $stats['D']++; $stats['PTS'] += 1; $stats['FORM'][] = 'D'; }
                else { $stats['L']++; $stats['FORM'][] = 'L'; }
            }
            return $stats + ['GD' => $stats['GF'] - $stats['GA']];
        };

        $rows = [];
        foreach ([0,1] as $i) {
            $t = $competitors[$i] ?? [];
            $id = (int)($t['id'] ?? 0);
            if (!$id) continue;
            $s = $calc($id);
            $rows[] = [
                'pos'      => 0,
                'teamId'   => $id,
                'teamName' => $t['name'] ?? ('Team '.($i+1)),
                'mp'       => $s['MP'],
                'w'        => $s['W'],
                'd'        => $s['D'],
                'l'        => $s['L'],
                'g'        => $s['GF'].':'.$s['GA'],
                'gd'       => $s['GD'],
                'pts'      => $s['PTS'],
                'form'     => $s['FORM'],
            ];
        }

        usort($rows, function($a,$b){
            if ($a['pts'] !== $b['pts']) return $b['pts'] - $a['pts'];
            if ($a['gd']  !== $b['gd'])  return $b['gd']  - $a['gd'];
            [$agf] = array_map('intval', explode(':', $a['g']));
            [$bgf] = array_map('intval', explode(':', $b['g']));
            return $bgf - $agf;
        });
        foreach ($rows as $i => &$r) $r['pos'] = $i+1;

        return $rows;
    }

    /* =========================
     *    PREV.EVENTS HELPERS
     * ========================= */

    /** Сохранить имя команды в локальный кэш по её ID. */
    private function cacheTeamName(?int $id, ?string $name): void
    {
        if ($id && $name && !isset($this->teamNameCache[$id])) {
            $this->teamNameCache[(int)$id] = $name;
        }
    }

    /** Вернуть имя команды по ID: сначала из переданного словаря, затем из кэша. */
    private function nameById(?int $id, array $fallbackById = []): ?string
    {
        if (!$id) return null;
        $id = (int)$id;
        if (!empty($fallbackById[$id])) return $fallbackById[$id];
        if (!empty($this->teamNameCache[$id])) return $this->teamNameCache[$id];
        return null;
    }

    /**
     * Дозаполнить по eventId через Events API: home/away id+name+score.
     * Используется, когда в previousEvents отсутствуют имена/счёт.
     */
    private function resolveTeamsFromDetails(int $eventId): array
    {
        if ($eventId <= 0) return [];

        if (!isset($this->eventDetailsCache[$eventId])) {
            $d = $this->fetchEventDetails($eventId);

            $homeId = $awayId = null;
            $homeName = $awayName = null;
            $scoreHome = $scoreAway = null;
            $byId = [];

            if (!empty($d['competitors']) && is_array($d['competitors'])) {
                foreach ($d['competitors'] as $c) {
                    $cid = $c['id'] ?? null;
                    if ($cid) $byId[(int)$cid] = $c['name'] ?? null;
                }
                foreach ($d['competitors'] as $c) {
                    $q = strtolower((string)($c['qualifier'] ?? ''));
                    if ($q === 'home') {
                        $homeId   = $c['id']   ?? $homeId;
                        $homeName = $c['name'] ?? $homeName;
                        if (isset($c['score'])) $scoreHome = (int)$c['score'];
                    } elseif ($q === 'away') {
                        $awayId   = $c['id']   ?? $awayId;
                        $awayName = $c['name'] ?? $awayName;
                        if (isset($c['score'])) $scoreAway = (int)$c['score'];
                    }
                }
            }

            if ((!$homeId || !$awayId) && !empty($d['teams']) && is_array($d['teams'])) {
                $th = $d['teams']['home'] ?? [];
                $ta = $d['teams']['away'] ?? [];
                if (($th['id'] ?? null) && !isset($byId[(int)$th['id']])) $byId[(int)$th['id']] = $th['name'] ?? null;
                if (($ta['id'] ?? null) && !isset($byId[(int)$ta['id']])) $byId[(int)$ta['id']] = $ta['name'] ?? null;

                $homeId   = $homeId   ?? ($th['id']   ?? null);
                $homeName = $homeName ?? ($th['name'] ?? null);
                $awayId   = $awayId   ?? ($ta['id']   ?? null);
                $awayName = $awayName ?? ($ta['name'] ?? null);
            }

            if ((!isset($scoreHome) || !isset($scoreAway)) && !empty($d['score']) && is_array($d['score'])) {
                $scoreHome = $scoreHome ?? ($d['score']['home'] ?? null);
                $scoreAway = $scoreAway ?? ($d['score']['away'] ?? null);
            }

            $this->eventDetailsCache[$eventId] = [
                'homeId'    => $homeId,
                'homeName'  => $homeName,
                'awayId'    => $awayId,
                'awayName'  => $awayName,
                'scoreHome' => is_null($scoreHome) ? null : (int)$scoreHome,
                'scoreAway' => is_null($scoreAway) ? null : (int)$scoreAway,
                'byId'      => $byId,
            ];
        }
        return $this->eventDetailsCache[$eventId];
    }

    /**
     * Резерв: подтянуть имена команд по ID из MatchCentre конкретного события.
     * Возвращает словарь byId[id] = name при успехе.
     */
    private function resolveTeamsFromMatchcentre(int $eventId): array
    {
        if ($eventId <= 0) return [];
        foreach (["Event-{$eventId}", (string)$eventId] as $evKey) {
            $res = $this->sgw->api->matchcentre->getMatchCentreEvent(
                $this->projectId, $this->sport, $evKey, []
            );
            $data = (isset($res['data']) && is_array($res['data'])) ? $res['data'] : (is_array($res) ? $res : []);
            $ev   = $data['event'] ?? [];
            $byId = [];
            foreach (($ev['competitors'] ?? []) as $c) {
                if (!empty($c['id']) && isset($c['name'])) $byId[(int)$c['id']] = $c['name'];
            }
            if ($byId) return ['byId' => $byId];
        }
        return [];
    }

    /**
     * Разбить previousEvents на 2 группы последних матчей:
     *  - t1: матчи команды 1 (с указанием соперника и H/A),
     *  - t2: матчи команды 2.
     *  - h2h: очные встречи между командами 1 и 2

     *
     * H2H полностью исключён (и из логики, и из результата).
     * При необходимости имена соперников восстанавливаются через Events/MatchCentre/кэш.
     *
     * @return array{
     *   t1:  array{title:string, teamName:string, items:array<int,array>},
     *   t2:  array{title:string, teamName:string, items:array<int,array>}
     * }
     */

    private function splitPreviousEvents(array $prev, ?int $t1Id, ?int $t2Id, string $t1Name, string $t2Name): array
    {
        // Берём не больше 15, чтобы не тормозить рендеринг
        $prev = is_array($prev) ? array_slice($prev, 0, 15) : [];

        $out = [
            't1'  => ['title' => "Last matches — {$t1Name}", 'teamName' => $t1Name, 'items' => []],
            't2'  => ['title' => "Last matches — {$t2Name}", 'teamName' => $t2Name, 'items' => []],
            'h2h' => ['title' => "{$t1Name} vs {$t2Name}",    'items'    => []],
        ];
        if (!$prev) return $out;

        foreach ($prev as $ev) {
            $evId = isset($ev['id']) ? (int)$ev['id'] : 0;

            $home = $ev['teams']['home'] ?? [];
            $away = $ev['teams']['away'] ?? [];

            $homeId   = $home['id']   ?? null;
            $awayId   = $away['id']   ?? null;
            $homeName = $home['name'] ?? null;
            $awayName = $away['name'] ?? null;

            $scoreHome = $ev['score']['home'] ?? null;
            $scoreAway = $ev['score']['away'] ?? null;

            // Минимальный базовый набор для строки
            $venueName = $ev['venue']['name'] ?? null;
            $city      = $ev['venue']['city'] ?? null;
            $venue     = trim(implode(', ', array_filter([$venueName, $city])));

            $base = [
                'id'     => $evId ?: null,
                'date'   => $ev['dateTime'] ?? null,
                'league' => $ev['league']['name']  ?? null,
                'round'  => $ev['league']['round'] ?? null,
                'venue'  => $venue ?: null,
            ];

            // --- H2H: обе текущие команды ---
            $isH2H = ($homeId && $awayId)
                && in_array($homeId, [$t1Id, $t2Id], true)
                && in_array($awayId, [$t1Id, $t2Id], true);

            if ($isH2H) {
                $out['h2h']['items'][] = $base + [
                    'homeName' => $homeName ?? 'Another team',
                    'awayName' => $awayName ?? 'Another team',
                    'score'    => (isset($scoreHome, $scoreAway) ? "{$scoreHome}–{$scoreAway}" : null),
                ];
                continue;
            }

            // --- Last matches — T1 ---
            if ($t1Id !== null && ($homeId === $t1Id || $awayId === $t1Id)) {
                $teamIsHome = ($homeId === $t1Id);
                $oppName    = $teamIsHome ? ($awayName ?? null) : ($homeName ?? null);
                if (!$oppName) $oppName = 'Another team';

                $out['t1']['items'][] = $base + [
                    'teamName'  => $t1Name,
                    'oppName'   => $oppName,
                    'homeAway'  => $teamIsHome ? 'H' : 'A',
                    'scoreTeam' => $teamIsHome ? $scoreHome : $scoreAway,
                    'scoreOpp'  => $teamIsHome ? $scoreAway : $scoreHome,
                    'score'     => (isset($scoreHome, $scoreAway)
                                    ? (($teamIsHome ? $scoreHome : $scoreAway) . '–' . ($teamIsHome ? $scoreAway : $scoreHome))
                                    : null),
                ];
                continue;
            }

            // --- Last matches — T2 ---
            if ($t2Id !== null && ($homeId === $t2Id || $awayId === $t2Id)) {
                $teamIsHome = ($homeId === $t2Id);
                $oppName    = $teamIsHome ? ($awayName ?? null) : ($homeName ?? null);
                if (!$oppName) $oppName = 'Another team';

                $out['t2']['items'][] = $base + [
                    'teamName'  => $t2Name,
                    'oppName'   => $oppName,
                    'homeAway'  => $teamIsHome ? 'H' : 'A',
                    'scoreTeam' => $teamIsHome ? $scoreHome : $scoreAway,
                    'scoreOpp'  => $teamIsHome ? $scoreAway : $scoreHome,
                    'score'     => (isset($scoreHome, $scoreAway)
                                    ? (($teamIsHome ? $scoreHome : $scoreAway) . '–' . ($teamIsHome ? $scoreAway : $scoreHome))
                                    : null),
                ];
            }
        }

        return $out;
    }

    /** Разбить highlights на 1-й и 2-й тайм по time.elapsed (<=45 → 1st, >45 → 2nd). */
    private function splitHighlightsByHalves(array $mcEvent): array
    {
        $details = $mcEvent['details'] ?? [];
        $hl      = $details['highlights'] ?? [];
        $home    = is_array($hl['home'] ?? null) ? $hl['home'] : [];
        $away    = is_array($hl['away'] ?? null) ? $hl['away'] : [];

        $split = function(array $list): array {
            $h1 = []; $h2 = [];
            foreach ($list as $ev) {
                $elapsed = (int)($ev['time']['elapsed'] ?? 0);
                // 45+X обычно передаётся как elapsed=45 и time.extra>0 — это тоже 1-й тайм.
                // Всё, что строго >45 — во 2-й тайм. (ET тоже попадёт во 2-й — по задаче нужно только 2 тайма)
                if ($elapsed <= 45) $h1[] = $ev;
                else                $h2[] = $ev;
            }
            return ['h1' => $h1, 'h2' => $h2];
        };

        return [
            'home' => $split($home),
            'away' => $split($away),
        ];
    }
}
