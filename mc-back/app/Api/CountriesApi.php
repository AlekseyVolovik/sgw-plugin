<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class CountriesApi extends Api{
    /**
     * Получить список стран по заданным параметрам.
     *
     * @param array $query Ассоциативный массив параметров запроса.
     *      <code>
     *          $query = [
     *              'id' => ?int
     *              'name' => ?string,
     *              'code' => ?string,
     *              'code3' => ?string,
     *              'region' => ?string,
     *              'sortBy' => ?string,
     *              'sortDirection' => ?string,
     *              'skip' => ?int,
     *              'take' => ?int,
     *          ];
     *      </code>
     * @return array
     */
    public function getCountries(array $query = []): array
    {
        return $this->getCached("/api/countries", $query);
    }
}