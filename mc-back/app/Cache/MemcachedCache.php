<?php declare(strict_types=1);

namespace SGW\Cache;

use Memcached;
use RuntimeException;
use SGW\Interface\CacheInterface;
use SGW\Service\EnvironmentService;

/**
 * Class MemcachedCache
 *
 * Реализация интерфейса CacheInterface с использованием расширения Memcached.
 *
 * Пример использования:
 *      <code>
 *          $cache = new MemcachedCache();
 *          $cache->set('key', 'value', 600);
 *          $data = $cache->get('key');
 *      </code>
 *
 * @package Sportsgateway\Cache
 */
class MemcachedCache implements CacheInterface
{
    private Memcached $memcached;

    public function __construct()
    {
        if (!extension_loaded('memcached')) throw new RuntimeException('Memcached extension not installed');

        $this->memcached = new Memcached();

        $connected = $this->memcached->addServer(
            EnvironmentService::get('cacheHost') ?: 'localhost',
            EnvironmentService::get('cachePort') ?: 11211
        );

        if (!$connected) throw new RuntimeException('Failed to connect to Memcached');
    }

    public function getStatus(): bool {
        return (bool)$this->memcached->getStats();
    }

    public function get(string $key): mixed
    {
        $value = $this->memcached->get($key);
        return $this->memcached->getResultCode() === Memcached::RES_NOTFOUND ? null : $value;
    }
    public function set(string $key, $value, int $ttl = 300): bool
    {
        return $this->memcached->set($key, $value, $ttl);
    }
    public function delete(string $key): bool
    {
        return $this->memcached->delete($key);
    }
}