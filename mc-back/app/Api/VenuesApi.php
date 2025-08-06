<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class VenuesApi extends Api {
    /**
     * Получить список мест проведения.
     *
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'id' => ?int
     *              'sport' => ?string
     *              'name' => ?string
     *              'city' => ?string
     *              'country' => ?string
     *              'source' => ?string (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'sources' => array|string|null (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'sportId' => ?string (None, Cricket, Football, Kabaddi)
     *              'urlSegment' => ?string
     *              'sortBy' => ?string
     *              'sortDirection' => ?string (Asc, Desc)
     *              'skip' => ?int
     *              'take' => ?int
     *          ];
     *      </code>
     * @return array
     */
    public function getVenues(array $query = []): array
    {
        return $this->getCached("api/venues", $query);
    }

    /**
     * Получить детальную информацию о месте проведения.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $urlSegment Уникальный сегмент URL для идентификации
     * @return array
     */
    public function getVenueDetails(int $projectId, string $sport, string $urlSegment): array
    {
        return $this->getCached("api/projects/$projectId/sports/$sport/venues/$urlSegment");
    }

    /**
     * Получить места проведения, сгруппированные по категории.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $category Идентификатор категории (All, Countries)
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'alphabetLetter' => ?string
     *              'countryUrlSegment' => ?string
     *              'countries' => array|int|null
     *              'searchTerms' => ?string
     *          ];
     *      </code>
     * @return array
     */
    public function getVenuesByCategory(int $projectId, string $sport, string $category, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/sports/$sport/venues/by-category/$category", $query);
    }

    /**
     * Получить доступные фильтры для мест проведения.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getVenueFilters(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/sports/$sport/venues/by-category/filters");
    }

    /**
     * Получить структурированные данные мест проведения.
     *
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getAiVenuesPayload(string $sport): array
    {
        return $this->getCached("api/venues/$sport/ai/match/payload");
    }
}