<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class LocationApi extends Api {
    /**
     * Получить географические данные о местоположении на основе IP-адреса запроса.
     *
     * @param string $ip IP адрес.
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'ip' => ?string
     *          ];
     *      </code>
     * @return array
     */
    public function getLocation(string $ip, array $query = []): array
    {
        return $this->getCached("/api/location", ['ip' => $ip, ...$query]);
    }
}