<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class LeagueApi extends Api {
    /**
     * Получить список лиг по заданным параметрам.
     *
     * @param array|string $sources Вид ресурса (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'sport' => ?string
     *              'source' => ?string, (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'sources' => array|string|null, (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'sportId' => ?string, (None, Cricket, Football, Kabaddi)
     *              'name' => ?string,
     *              'unionKey' => ?string,
     *              'onlyMerged' => ?bool,
     *              'sortBy' => ?string,
     *              'sortDirection' => ?string, (Asc, Desc)
     *              'skip' => ?int,
     *              'take' => ?int,
     *          ];
     *      </code>
     * @return array
     */
    public function getLeagues(array|string $sources, array $query = []): array
    {
        return $this->getCached("api/leagues", ['sources' => $sources, ...$query]);
    }

    /**
     * Получить расписание и информацию о лиге по её URL-сегменту.
     *
     * @param int $projectId ID проекта.
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $urlSegment Уникальный сегмент URL для идентификации
     * @return array
     */
    public function getLeagueSchedule(int $projectId, string $sport, string $urlSegment): array
    {
        return $this->getCached("/api/projects/$projectId/$sport/leagues/$urlSegment/schedule");
    }

    /**
     * Получить структурированные данные лиг.
     *
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getAiLeaguesPayload(string $sport): array
    {
        return $this->getCached("api/leagues/$sport/ai/match/payload");
    }
}