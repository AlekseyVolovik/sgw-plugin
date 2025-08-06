<?php declare(strict_types=1);

namespace SGW\Interface;

/**
 * Interface CacheInterface
 *
 * Определяет контракт для сервисов кеширования, поддерживающих базовые операции:
 * получение, сохранение и удаление данных по ключу.
 */
interface CacheInterface
{
    /**
     * Проверяет работоспособность кеш сервиса.
     *
     * @return bool
     */
    public function getStatus(): bool;

    /**
     * Получает значение из кеша по заданному ключу.
     *
     * @param string $key Ключ кеша.
     * @return mixed Значение, связанное с ключом, или null, если ключ не найден.
     */
    public function get(string $key): mixed;

    /**
     * Сохраняет значение в кеш по заданному ключу.
     *
     * @param string $key Ключ кеша.
     * @param mixed $value Значение для сохранения.
     * @param int $ttl Время жизни кеша в секундах (по умолчанию 300 секунд).
     * @return bool Возвращает true при успешном сохранении, иначе false.
     */
    public function set(string $key, $value, int $ttl = 300): bool;

    /**
     * Удаляет значение из кеша по заданному ключу.
     *
     * @param string $key Ключ кеша.
     * @return bool Возвращает true, если удаление прошло успешно, иначе false.
     */
    public function delete(string $key): bool;
}