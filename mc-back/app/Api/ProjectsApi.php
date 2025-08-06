<?php declare(strict_types=1);

namespace SGW\Api;

use SGW\Class\Api;

class ProjectsApi extends Api {
    /**
     * Получить список всех доступных проектов с базовой информацией.
     *
     * @return array
     */
    public function getProjects(): array
    {
        return $this->getCached("api/projects");
    }
}