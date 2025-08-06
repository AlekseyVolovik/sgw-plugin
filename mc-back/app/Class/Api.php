<?php declare(strict_types=1);

namespace SGW\Class;

use SGW\Service\CacheService;
use SGW\Service\EnvironmentService;
use SGW\Service\HttpService;
use Throwable;

class Api
{
    private CacheService $cache;
    private HttpService $http;

    private int $expiresTime;

    public function __construct()
    {
        $this->cache = CacheService::getInstance();
        $this->http = HttpService::getInstance();
        $this->expiresTime = EnvironmentService::get('expiresTime') ?? 300;
    }

    protected function getCached($uri, $query = [])
    {
        if(!$this->cache->status) return $this->http->get($uri, $query);

        $cache_key = hash('md5', $uri . '?' . http_build_query($query));
        $cached_response = $this->cache->get($cache_key);

        if (!$cached_response) {
            try {
                $cached_response = $this->http->get($uri, $query);
                $this->cache->set($cache_key, $cached_response, $this->expiresTime);
            } catch (Throwable $e) {
                $cached_response = [];
            }
        }

        return $cached_response;
    }
}