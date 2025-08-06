<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class OddsCentreApi extends Api
{
    /**
     * Получить конфигурационные данные центра коэффициентов для указанного вида спорта.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *       <code>
     *           $query = [
     *               'collapsed' => ?bool
     *           ];
     *       </code>
     * @return array
     */
    public function getOddsCentreConfiguration(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/oddscentre/$sport/configuration", $query);
    }

    /**
     * Получить контент для указанного ключа контента в центре коэффициентов.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $contentKey Ключ контента
     * @return array
     */
    public function getOddsCentreContent(int $projectId, string $sport, string $contentKey): array
    {
        return $this->getCached("api/projects/$projectId/oddscentre/$sport/configuration/$contentKey/content");
    }

    /**
     * Получить основные данные центра коэффициентов для указанного вида спорта.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *        <code>
     *            $query = [
     *                'contentKey' => ?string
     *                'period' => ?string (Live, Today, Upcoming, Finished)
     *                'status' => ?string (Unknown, Upcoming, Live, Finished, Abandoned, Interrupted, Postponed, Cancelled, NotPlayed, Queued)
     *                'dates' => array|string|null
     *                'league' => ?string
     *                'competition' => ?string
     *            ];
     *        </code>
     * @return array
     */
    public function getOddsCentreData(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/oddscentre/$sport", $query);
    }

    /**
     * Получить список событий в центре коэффициентов.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *        <code>
     *            $query = [
     *                'id' => ?int
     *                'leagueId' => ?int
     *                'league' => ?string
     *                'competitionId' => ?int
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
     *                'skip' => ?take
     *            ];
     *        </code>
     * @return array
     */
    public function getOddsCentreEvents(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/oddscentre/$sport/events", $query);
    }

    /**
     * Получить детальную информацию о конкретном событии в центре коэффициентов.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param array $query Ассоциативный массив параметров запроса.
     *        <code>
     *            $query = [
     *                'event' => ?string
     *                'section' => ?string
     *                'league' => ?string
     *                'competition' => ?string
     *            ];
     *        </code>
     * @return array
     */
    public function getOddsCentreEvent(int $projectId, string $sport, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/oddscentre/$sport/event", $query);
    }

    /**
     * Получить список категорий центра коэффициентов.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getOddsCentreCategories(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/oddscentre/$sport/categories");
    }

    /**
     * Получить список записей центра коэффициентов.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getOddsCentreEntries(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/oddscentre/$sport/entries");
    }
}