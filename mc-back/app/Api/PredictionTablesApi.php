<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class PredictionTablesApi extends Api {
    /**
     * Получить список событий, связанных с таблицей прогнозов.
     *
     * @param int $projectId ID проекта
     * @param int $predictionTableId ID таблицы прогнозов
     * @return array
     */
    public function getPredictionTableEvents(int $projectId, int $predictionTableId): array
    {
        return $this->getCached("api/projects/$projectId/predictiontables/$predictionTableId/events");
    }

    /**
     * Получить детальную информацию о таблице прогнозов.
     *
     * @param int $projectId ID проекта
     * @param int $predictionTableId ID таблицы прогнозов
     * @return array
     */
    public function getPredictionTableDetails(int $projectId, int $predictionTableId): array
    {
        return $this->getCached("api/projects/$projectId/predictiontables/$predictionTableId");
    }

    /**
     * Получить список таблиц прогнозов для проекта.
     *
     * @param int $projectId ID проекта
     * @param array $query Ассоциативный массив параметров запроса.
     *         <code>
     *             $query = [
     *                 'source' => ?string (Unknown, Parimatch, SportsRadar, SportsApi, BlaskApi, SportRoanuz, Internal)
     *                 'ids' => array|int|null
     *                 'sortBy' => ?string
     *                 'sortDirection' => ?string (Asc, Desc)
     *                 'skip' => ?int
     *                 'take' => ?int
     *             ];
     *         </code>
     * @return array
     */
    public function getPredictionTables(int $projectId, array $query = []): array
    {
        return $this->getCached("api/projects/$projectId/predictiontables", $query);
    }

    /**
     * Получить список доступных периодов для таблиц прогнозов.
     *
     * @return array
     */
    public function getPredictionPeriods(): array
    {
        return $this->getCached("api/predictiontables/periods");
    }
}