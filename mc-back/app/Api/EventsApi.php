<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class EventsApi extends Api
{
    /**
     * Получить список событий по заданным параметрам.
     *
     * @param string $source Вид ресурса (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'projectId' => ?int
     *              'id' => ?int
     *              'sportId' => ?int
     *              'sport' => ?string
     *              'leagueId' => ?int
     *              'league' => ?string
     *              'competitionId' => ?int
     *              'competition' => ?string
     *              'source' => ?string (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'status' => ?string (Unknown, Upcoming, Live, Finished, Abandoned, Interrupted, Postponed, Cancelled, NotPlayed, Queued)
     *              'statuses' => array|string|null (Unknown, Upcoming, Live, Finished, Abandoned, Interrupted, Postponed, Cancelled, NotPlayed, Queued)
     *              'fromDate' => ?string
     *              'toDate' => ?string
     *              'sortBy' => ?string
     *              'sortDirection' => ?string
     *              'skip' => ?int
     *              'take' => ?int
     *          ];
     *      </code>
     * @return array
     */
    public function getEvents(string $source, array $query = []): array
    {
        return $this->getCached("api/events", ['source' => $source, ...$query]);
    }

    /**
     * Получить полную информацию о конкретном событии по его ID.
     *
     * @param int $eventId ID события.
     * @return array
     */
    public function getEventById(int $eventId): array
    {
        return $this->getCached("/api/events/$eventId");
    }

    /**
     * Получить информацию о конкретном событии по Url прогноза.
     *
     * @param array $query Ассоциативный массив параметров запроса.
     *       <code>
     *           $query = [
     *               'predictionUrl' => ?string
     *           ];
     *       </code>
     * @return array
     */
    public function getEventByUrl(array $query = []): array
    {
        return $this->getCached("api/events/by-url", $query);
    }

    /**
     * Получить URL прогноза по ID события.
     *
     * @param int $projectId ID проекта.
     * @param int $eventId ID события.
     * @return array
     */
    public function getEventPredictionUrl(int $projectId, int $eventId): array
    {
        return $this->getCached("api/projects/$projectId/events/$eventId/prediction");
    }
}