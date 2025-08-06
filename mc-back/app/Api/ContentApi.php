<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class ContentApi extends Api{
    /**
     * Получить структурированный контент по указанному типу и ключу.
     *
     * @param int $projectId ID проекта
     * @param string $type Тип контента (Unknown, Sport, League, Competition, Event, Competitor, Player, Venue, Country)
     * @param string $key Уникальный идентификатор контента
     * @return array
     */
    public function getContentByTypeAndKey(int $projectId, string $type, string $key): array
    {
        return $this->getCached("/api/projects/$projectId/content/$type/$key");
    }
}