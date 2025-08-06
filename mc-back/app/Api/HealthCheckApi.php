<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;
use SGW\Service\HttpService;

class HealthCheckApi extends Api{
    /**
     * Проверяет работоспособность API.
     *
     * @return bool
     */
    public function getHealthCheck(): bool
    {
        $http = HttpService::getInstance();
        $response = $http->request("GET", "/api/healthcheck", [], false);
        return $response['success'];
    }
}