<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class MatchCentreApi extends Api
{
    /**
     * Получить конфигурационные данные матч-центра для указанного вида спорта
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
    public function getMatchCentreConfiguration(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport/configuration", $query);
    }

    /**
     * Получить контент для указанного ключа контента в матч-центре
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $contentKey Ключ контента
     * @return array
     */
    public function getMatchCentreContent(int $projectId, string $sport, string $contentKey): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport/configuration/$contentKey/content");
    }

    /**
     * Получить основные данные матч-центра для указанного вида спорта
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *       <code>
     *           $query = [
     *               'contentKey' => ?string
     *               'period' => ?string (Live, Today, Upcoming, Finished)
     *               'status' => ?string (Unknown, Upcoming, Live, Finished, Abandoned, Interrupted, Postponed, Cancelled, NotPlayed, Queued)
     *               'dates' => array|string|null
     *               'league' => ?string
     *               'competition' => ?string
     *           ];
     *       </code>
     * @return array
     */
    public function getMatchCentreData(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport", $query);
    }

    /**
     * Получить список событий в матч-центре
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *        <code>
     *            $query = [
     *                'id' => ?int
     *                'leagueId' => ?int
     *                'leagueIds' => array|int|null
     *                'leagueUnionKey' => ?string
     *                'league' => ?string
     *                'competitionId' => ?int
     *                'competitionIds' => array|int|null
     *                'competitionUnionKey' => ?string
     *                'competition' => ?string
     *                'url' => ?string
     *                'status' => ?string (Unknown, Upcoming, Live, Finished, Abandoned, Interrupted, Postponed, Cancelled, NotPlayed, Queued)
     *                'period' => ?string (Live, Today, Upcoming, Finished)
     *                'dates' => array|string|null
     *                'fromDate' => ?string
     *                'toDate' => ?string
     *                'sortBy' => ?string
     *                'sortDirection' => ?string (Asc, Desc)
     *                'skip' => ?int
     *                'take' => ?int
     *            ];
     *        </code>
     * @return array
     */
    public function getMatchCentreEvents(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport/events", $query);
    }

    /**
     * Получить сводную информацию по лигам с группировкой событий по статусам
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *         <code>
     *             $query = [
     *                 'eventsCount' => ?int
     *             ];
     *         </code>
     * @return array
     */
    public function getMatchCentreLeaguesSummary(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport/leagues/summary", $query);
    }

    /**
     * Получить детальную информацию о конкретном событии
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $event Событие //TODO: Не известный тип данных (id, url или ключ)
     * @param array $query Ассоциативный массив параметров запроса.
     *         <code>
     *             $query = [
     *                 'event' => ?string
     *                 'section' => ?string
     *                 'league' => ?string
     *                 'competition' => ?string
     *             ];
     *         </code>
     * @return array
     */
    public function getMatchCentreEvent(int $projectId, string $sport, string $event, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport/event", ['event' => $event, ...$query]);
    }

    /**
     * Получить хайлайты (ключевые моменты) матча по крикету
     *
     * @param int $projectId ID проекта
     * @param int $eventId ID события
     * @return array
     */
    public function getCricketMatchHighlights(int $projectId, int $eventId): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/cricket/event/$eventId/highlights");
    }

    /**
     * Получить список категорий матч-центра
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getMatchCentreCategories(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport/categories");
    }

    /**
     * Получить список записей матч-центра
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getMatchCentreEntries(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport/entries");
    }

    /**
     * Получить статистическую информацию по матч-центру
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getMatchCentreStatistics(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/matchcentre/$sport/statistics");
    }

    /**
     * Получить список источников данных для матч-центра
     *
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getMatchCentreSources(string $sport): array
    {
        return $this->getCached("api/matchcentre/$sport/sources");
    }
}