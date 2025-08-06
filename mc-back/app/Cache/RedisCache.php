<?php declare(strict_types=1);

namespace SGW\Cache;

use SGW\Interface\CacheInterface;
use Redis;
use RuntimeException;
use SGW\Service\EnvironmentService;

/**
 * Class RedisCache
 *
 * Реализация интерфейса CacheInterface с использованием расширения Redis.
 *
 * Пример использования:
 *      <code>
 *          $cache = new RedisCache();
 *          $cache->set('key', 'value', 600);
 *          $data = $cache->get('key');
 *      </code>
 *
 * @package Sportsgateway\Cache
 */
class RedisCache implements CacheInterface
{
    private Redis $redis;

    public function __construct()
    {
        if (!extension_loaded('redis')) throw new RuntimeException('Redis extension not installed');

        $this->redis = new Redis();

        $connected = $this->redis->connect(
            EnvironmentService::get('cacheHost') ?: 'localhost',
            EnvironmentService::get('cachePort') ?: 6379
        );

        if (!$connected) throw new RuntimeException('Failed to connect to Redis');
    }

    public function getStatus(): bool {
        return (bool)$this->redis->ping();
    }

    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value === false ? null : $value;
    }
    public function set(string $key, $value, int $ttl = 300): bool
    {
        return $this->redis->set($key, $value, $ttl);
    }
    public function delete(string $key): bool
    {
        return (bool)$this->redis->del($key);
    }
}