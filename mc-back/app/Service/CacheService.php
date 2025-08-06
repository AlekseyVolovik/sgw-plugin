<?php declare(strict_types=1);

namespace SGW\Service;

use SGW\Cache\MemcachedCache;
use SGW\Cache\RedisCache;
use SGW\Interface\CacheInterface;

/**
 * Class CacheService
 *
 * Универсальный сервис кеширования, автоматически выбирающий доступный драйвер кеша.
 * Реализует интерфейс CacheInterface и делегирует вызовы конкретному драйверу (Memcached или Redis).
 *
 * Поддерживаемые драйверы:
 * - Memcached (если установлено расширение `memcached`)
 * - Redis (если установлено расширение `redis`)
 *
 * Пример использования:
 *       <code>
 *           $cache = CacheService::getInstance();
 *           $cache->set('key', 'value', 600);
 *           $data = $cache->get('key');
 *       </code>
 *
 * @package Sportsgateway\Service
 */
class CacheService implements CacheInterface
{
    const LOG_CHANNEL = 'cache';
    private static ?self $instance = null;
    private CacheInterface $driver;

    /**
     *  Переменная для проверки доступности кеша
     */
    public string|bool $status = false;

    private function __construct()
    {
        $cacheDrivers = [
            'memcached' => MemcachedCache::class,
            'redis' => RedisCache::class,
        ];

        foreach ($cacheDrivers as $ext => $driverClass) {
            if (extension_loaded($ext)) {
                try {
                    $this->driver = new $driverClass();
                    $this->status = $this->getStatus() !== false ? $ext : false;
                    return;
                } catch (\Exception $e) {
                    LogService::warning(self::LOG_CHANNEL, "$ext: " . $e->getMessage());
                    continue;
                }
            }
        }
    }

    /**
     * Получает единственный экземпляр CacheService (Singleton).
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getStatus(): bool
    {
        return $this->driver->getStatus();
    }

    public function get(string $key): mixed
    {
        return $this->driver->get($key);
    }

    public function set(string $key, $value, int $ttl = 300): bool
    {
        return $this->driver->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->driver->delete($key);
    }
}