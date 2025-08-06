<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class UsersApi extends Api {
    /**
     * Получить список ролей пользователей.
     *
     * @return array
     */
    public function getUserRoles(): array
    {
        return $this->getCached("/api/users/roles");
    }

    /**
     * Получить список прав доступа с привязкой к ролям.
     *
     * @return array
     */
    public function getUserPermissions(): array
    {
        return $this->getCached("api/users/permissions");
    }

    /**
     * Получить информацию о текущем аутентифицированном пользователе.
     *
     * @return array
     */
    public function getCurrentUser(): array
    {
        return $this->getCached("api/users/me");
    }

    /**
     * Получить информацию о пользователе по его ID.
     *
     * @param int $id ID пользователя
     * @return array
     */
    public function getUserById(int $id): array
    {
        return $this->getCached("api/users/$id");
    }

    /**
     * Получить список пользователей.
     *
     * @param array $query Ассоциативный массив параметров запроса.
     *          <code>
     *              $query = [
     *                  'skip' => ?int
     *                  'take' => ?int
     *              ];
     *          </code>
     * @return array
     */
    public function getUsers(array $query = []): array
    {
        return $this->getCached("api/users");
    }
}