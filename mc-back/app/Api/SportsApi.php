<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class SportsApi extends Api {
    /**
     * Получить список всех доступных видов спорта.
     *
     * @return array
     */
    public function getSports(): array
    {
        return $this->getCached("api/sports");
    }

    /**
     * Получить расписание лиг и соревнований для указанного вида спорта.
     *
     * @param int $projectId ID проекта
     * @param string $sport Вид спорта (None, Cricket, Football, Kabaddi)
     * @return array
     */
    public function getSportSchedule(int $projectId, string $sport): array
    {
        return $this->getCached("api/projects/$projectId/$sport/schedule");
    }
}