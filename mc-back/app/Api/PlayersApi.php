<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class PlayersApi extends Api {
    /**
     * Поулчить список игроков.
     *
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'id' => ?int
     *              'ids' => array|int|null
     *              'sport' => ?string
     *              'name' => ?string
     *              'country' => ?string
     *              'competitor' => ?string
     *              'competitorId' => ?int
     *              'competitorUnionKey' => ?string
     *              'source' => ?string (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'sources' => array|string|null (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'urlSegment' => ?string
     *              'onlyMerged' => ?bool
     *              'sortBy' => ?string
     *              'sortDirection' => ?string (Asc, Desc)
     *              'skip' => ?int
     *              'take' => ?int
     *          ];
     *      </code>
     * @return array
     */
    public function getPlayers(array $query = []): array
    {
        return $this->getCached("api/players", $query);
    }

    /**
     * Получить детальную информацию об игроке по его URL-сегменту.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $urlSegment URL-идентификатор игрока
     * @return array
     */
    public function getPlayerDetails(int $projectId, string $sport, string $urlSegment): array
    {
        return $this->getCached("api/projects/$projectId/sports/$sport/players/$urlSegment");
    }

    /**
     * Получить игроков, сгруппированных по указанной категории.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $category Идентификатор категории (All, Countries, Competitions, Competitors, Men, Women)
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'alphabetLetter' => ?string
     *              'competitors' => array|int|null
     *              'competitorUrlSegment' => ?string
     *              'countries' => array|int|null
     *              'countryUrlSegment' => ?string
     *              'competitions' => array|int|null
     *              'competitionUrlSegment' => ?string
     *              'ageStart' => ?int
     *              'ageEnd' => ?int
     *              'gender' => ?string (Unknown, Women, Men)
     *              'searchTerms' => ?string
     *              'roles' => array|string|null
     *          ];
     *      </code>
     * @return array
     */
    public function getPlayersByCategory(int $projectId, string $sport, string $category, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/sports/$sport/players/by-category/$category", $query);
    }

    /**
     * Получить доступные фильтры для поиска игроков.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getPlayerFilters(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/sports/$sport/players/by-category/filters");
    }

    /**
     * Получить структурированные данные игроков по указанному соревнованию.
     *
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $competitionUnionKey Уникальный ключ соревнования
     * @return array
     */
    public function getAiPlayersByCompetition(string $sport, string $competitionUnionKey): array
    {
        return $this->getCached("api/players/$sport/ai/match/$competitionUnionKey/payload");
    }

    /**
     * Получить универсальные данные игроков.
     *
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getAiPlayersPayload(string $sport): array
    {
        return $this->getCached("api/players/$sport/ai/match/payload");
    }
}