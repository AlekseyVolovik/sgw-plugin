<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class CompetitionsApi extends Api
{
    /**
     * Получить список соревнований по заданным параметрам.
     *
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'id' => ?int
     *              'ids' => int|array|null,
     *              'sportId' => ?string, (None, Cricket, Football, Kabaddi)
     *              'leagueId' => ?int,
     *              'leagueUnionKey' => ?string,
     *              'name' => ?string,
     *              'leagueName' => ?string,
     *              'urlSegment' => ?string,
     *              'source' => ?string, (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'sources' => array|string|null, (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'hasEvents' => ?bool,
     *              'onlyMerged' => ?bool,
     *              'skip' => ?int,
     *              'take' => ?int,
     *          ];
     *      </code>
     * @return array
     */
    public function getCompetitions(array $query = []): array
    {
        return $this->getCached("api/competitions", $query);
    }

    /**
     * Получить список соревнований с подробным содержанием по заданным параметрам.
     *
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'id' => ?int
     *              'ids' => array|int|null,
     *              'sportId' => ?string, (None, Cricket, Football, Kabaddi)
     *              'leagueId' => ?int,
     *              'name' => ?string,
     *              'leagueName' => ?string,
     *              'urlSegment' => ?string,
     *              'source' => ?string, (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *              'hasEvents' => ?bool,
     *              'skip' => ?int,
     *              'take' => ?int,
     *          ];
     *      </code>
     * @return array
     */
    public function getCompetitionsSummary(array $query = []): array
    {
        return $this->getCached("api/competitions/summary", $query);
    }

    /**
     * Получить список мест проведения для соревнования по его URL-сегменту и виду спорта.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $urlSegment Уникальный сегмент URL для идентификации
     * @return array
     */
    public function getCompetitionVenues(int $projectId, string $sport, string $urlSegment): array
    {
        return $this->getCached("api/projects/$projectId/$sport/competitions/$urlSegment/venues");
    }

    /**
     * Получить список мест проведения для соревнования с учётом лиги.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $leagueUrlSegment Уникальный идентификатор лиги в URL
     * @param string $urlSegment Уникальный сегмент URL для идентификации
     * @return array
     */
    public function getLeagueCompetitionVenues(int $projectId, string $sport, string $leagueUrlSegment, string $urlSegment): array
    {
        return $this->getCached("api/projects/$projectId/$sport/leagues/$leagueUrlSegment/competitions/$urlSegment/venues");
    }

    /**
     * Получить расписание событий для указанного соревнования.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $urlSegment Уникальный сегмент URL для идентификации
     * @return array
     */
    public function getCompetitionSchedule(int $projectId, string $sport, string $urlSegment): array
    {
        return $this->getCached("api/projects/$projectId/$sport/competitions/$urlSegment/schedule");
    }

    /**
     * Получить расписание событий для соревнования в контексте лиги.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $leagueUrlSegment Уникальный идентификатор лиги в URL
     * @param string $urlSegment Уникальный сегмент URL для идентификации
     * @return array
     */
    public function getLeagueCompetitionSchedule(int $projectId, string $sport, string $leagueUrlSegment, string $urlSegment): array
    {
        return $this->getCached("api/projects/$projectId/$sport/leagues/$leagueUrlSegment/competitions/$urlSegment/schedule");
    }

    /**
     * Возвращает структурированные данные на основе ключа лиги.
     *
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @param string $leagueUnionKey Уникальный идентификатор лиги в URL
     * @return array
     */
    public function getAiCompetitionPayload(string $sport, string $leagueUnionKey): array
    {
        return $this->getCached("api/competitions/$sport/ai/match/$leagueUnionKey/payload");
    }
}