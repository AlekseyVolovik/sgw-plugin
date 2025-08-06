<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class StatisticsApi extends Api
{
    /**
     * Получить конфигурацию статистики для указанного вида спорта.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'collapsed' => ?bool
     *          ];
     *      </code>
     * @return array
     */
    public function getStatisticsConfiguration(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/configuration", $query);
    }

    /**
     * Получить контент статистики по ключу контента.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $contentKey Ключ контента
     * @return array
     */
    public function getStatisticsContent(int $projectId, string $sport, string $contentKey): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/configuration/$contentKey/content");
    }

    /**
     * Получить параметры статистики для соревнования по крикету.
     *
     * @param int $projectId ID проекта
     * @param int $competitionId ID соревнования
     * @return array
     */
    public function getCricketStatisticsOptions(int $projectId, int $competitionId): array
    {
        return $this->getCached("api/projects/$projectId/statistics/cricket/competitions/$competitionId/options");
    }

    /**
     * Получить параметры статистики для соревнования по кабади.
     *
     * @param int $projectId ID проекта
     * @param int $competitionId ID соревнования
     * @return array
     */
    public function getKabaddyStatisticsOptions(int $projectId, int $competitionId): array
    {
        return $this->getCached("api/projects/$projectId/statistics/kabaddi/competitions/$competitionId/options");
    }

    /**
     * Получить список соревнований по фильтру.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'leagueId' => ?int
     *              'league' => ?string
     *              'name' => ?string
     *              'urlSegment' => ?string
     *              'sortBy' => ?string
     *              'sortDirection' => ?string (Asc, Desc)
     *              'skip' => ?int
     *              'take' => ?int
     *          ];
     *      </code>
     * @return array
     */
    public function getFilteredCompetitions(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/competitions/by-filter", $query);
    }

    /**
     * Получить подробную информацию статистики по виду спорта.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getStatisticsSummary(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/summary");
    }

    /**
     * Получить список соревнований со статистикой.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getStatisticsCompetitions(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/competitions");
    }

    /**
     * Получить список соревнований с подробной информацией и статистикой.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array|int $ids ID компетишен(а/ов)
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'ids' => array|int|null
     *          ];
     *      </code>
     * @return array
     */
    public function getStatisticsCompetitionsSummary(int $projectId, string $sport, array|int $ids, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/competitions/summary", ['ids' => $ids, ...$query]);
    }

    /**
     * Получить список лиг со статистикой.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getStatisticsLeagues(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/leagues");
    }

    /**
     * Получить статистику по конкретной лиге.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $leagueUrl URL лиги
     * @return array
     */
    public function getLeagueStatistics(int $projectId, string $sport, string $leagueUrl): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/leagues/$leagueUrl");
    }

    /**
     * Получить детальную статистику по соревнованию.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $leagueUrl URL лиги
     * @param string $competitionUrl URL соревнования
     * @return array
     */
    public function getLeagueCompetitionStatistics(int $projectId, string $sport, string $leagueUrl, string $competitionUrl): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/leagues/$leagueUrl/competitions/$competitionUrl");
    }

    /**
     * Получить записи статистики.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getStatisticsEntries(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/statistics/$sport/entries");
    }
}