<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class CompetitorsApi extends Api{
    /**
     * Получить полную информацию о команде по URL-сегменту.
     *
     * @param int $projectId ID проекта
     * @param string $urlSegment Уникальный сегмент URL для идентификации
     * @return array
     */
    public function getCompetitorDetails(int $projectId, string $urlSegment): array
    {
        return $this->getCached("api/projects/$projectId/competitors/$urlSegment");
    }

    /**
     * Получить данные команды с фильтрацией по виду спорта.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $urlSegment Уникальный сегмент URL для идентификации
     * @return array
     */
    public function getSportCompetitorDetails(int $projectId, string $sport, string $urlSegment): array
    {
        return $this->getCached("/api/projects/$projectId/sports/$sport/competitors/$urlSegment");
    }

    /**
     * Получить список команд по категории.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $category Категория (All, Global, National, Competitions, Men, Women)
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'countryUrlSegment' => ?string
     *              'countries' => array|int|null
     *              'competitionUrlSegment' => ?string
     *              'competitions' => array|int|null
     *              'activeYearsStart' => ?int
     *              'activeYearsEnd' => ?int
     *              'searchTerms' => ?string
     *              'gender' => ?string
     *          ];
     *      </code>
     * @return array
     */
    public function getCompetitorsByCategory(int $projectId, string $sport, string $category, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/sports/$sport/competitors/by-category/$category");
    }

    /**
     * Получить фильтры для категорий команд.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getCompetitorCategoryFilters(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/sports/$sport/competitors/by-category/filters");
    }

    /**
     * Получить структурированные данные команд по ключу соревнования.
     *
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $competitionUnionKey Union Key компетитора
     * @return array
     */
    public function getAiCompetitorsByCompetition(string $sport, string $competitionUnionKey): array
    {
        return $this->getCached("api/competitors/$sport/ai/match/$competitionUnionKey/payload");
    }

    /**
     * Получить универсальные данные участников без привязки к конкретному соревнованию.
     *
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getAiCompetitorsPayload(string $sport): array
    {
        return $this->getCached("api/competitors/$sport/ai/match/payload");
    }
}