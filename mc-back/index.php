<?php

use SGW\Api\ApiProvider;
use SGW\Service\CacheService;
use SGW\Service\EnvironmentService;
use SGW\Service\HttpService;

/**
 * Class SGWClient
 *
 * Основной класс-клиент для взаимодействия с API SportsGateway.
 * Реализует паттерн Singleton для обеспечения единственного экземпляра класса.
 * Предоставляет доступ к основным сервисам: HTTP-клиент, кеширование и провайдер API.
 *
 * Пример использования:
 * <code>
 * // Первый вызов необходимо делать с помощью метода ::create([]) что бы добавить конфиг
 * $sgw = SGWClient::create([
 *      'baseUrl' => 'https://example.site',
 *      'baseAuth' => 'Basic exampleAuth',
 *      'cacheHost' => 'memcached',
 *      'cacheExpires' => 60,
 * ]);
 *
 * // Получение единственного экземпляра клиента
 * $client = SGWClient::getInstance();
 *
 * // Доступ к HTTP-сервису
 * $response = $client->http->get('api/some_endpoint');
 *
 * // Доступ к сервису кеширования
 * $client->cache->set('my_key', 'my_value', 3600);
 * $cachedData = $client->cache->get('my_key');
 *
 * // Доступ к API провайдеру (и конкретным API)
 * $competitions = $client->api->competitions->getCompetitions();
 * $countries = $client->api->countries->getCountries();
 * </code>
 *
 */
final class SGWClient
{
    private static ?self $instance = null;
    public HttpService $http;
    public CacheService $cache;
    public ApiProvider $api;

    private function __construct()
    {
        $this->defines();
        $this->bootstrap();
        $this->services();
    }

    private function __clone()
    {
    }

    private function defines()
    {
        define('SGW_PATH_ROOT', __DIR__ . '/');
        define('SGW_PATH_LOGS', SGW_PATH_ROOT . 'logs/');
    }

    private function bootstrap(): void
    {
        /* Composer autoload */
        require_once "vendor/autoload.php";
    }

    private function services(): void
    {
        $this->http = HttpService::getInstance();
        $this->cache = CacheService::getInstance();
        $this->api = new ApiProvider();
    }

    /**
     * Получает единственный экземпляр HttpService (singleton).
     *
     * @return RuntimeException|self
     */
    public static function getInstance(): RuntimeException|self
    {
        if (self::$instance === null) throw new RuntimeException('SGWClient not initialized. Call SGWClient::create() first.');

        return self::$instance;
    }

    /**
     * Инициализация клиента, задает переменные окружения
     *
     * @param $config array{
     *     baseUrl: string,
     *     baseAuth: string,
     *     cacheHost: string,
     *     cachePort: int,
     *     cacheExpires: int
     * }
     *
     * @return RuntimeException|self
     */
    public static function create(array $config): RuntimeException|self
    {
        if (self::$instance !== null) throw new RuntimeException('SGWClient already initialized. Use SGWClient::getInstance() to retrieve it.');

        require_once "vendor/autoload.php";

        EnvironmentService::set('baseUrl', $config['baseUrl'] ?? null);
        EnvironmentService::set('baseAuth', $config['baseAuth'] ?? null);
        EnvironmentService::set('cacheHost', $config['cacheHost'] ?? null);
        EnvironmentService::set('cachePort', $config['cachePort'] ?? null);
        EnvironmentService::set('cacheExpires', $config['cacheExpires'] ?? null);

        self::$instance = new self();
        return self::$instance;
    }
}
