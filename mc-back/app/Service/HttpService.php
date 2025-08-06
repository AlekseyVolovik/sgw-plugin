<?php declare(strict_types=1);

namespace SGW\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Class HttpService
 *
 * Сервис-обёртка над GuzzleHttp\Client с поддержкой singleton-паттерна, автоматической конфигурацией
 * для production/staging окружений, retry-мидлваром и обработкой ошибок.
 *
 * Используется для выполнения HTTP-запросов к внешним API с поддержкой повторов в случае ошибок.
 *
 * Пример использования:
 *        <code>
 *            $http = HttpService::getInstance();
 *            $http->get('api/competitions', ['id' => 2]);
 *        </code>
 *
 * @package Sportsgateway\Service
 */
class HttpService
{
    const LOG_CHANNEL = 'http';

    private static ?self $instance = null;
    private Client $client;

    /**
     *  Переменная для проверки доступности http
     */
    public string|bool $status = false;

    private function __construct()
    {
        $baseUrl = EnvironmentService::get('baseUrl');
        $baseAuth = EnvironmentService::get('baseAuth');

        if (!$baseUrl) return;

        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => $baseAuth,
                'Accept' => 'application/json',
            ],
            'handler' => $this->withRetryMiddleware(),
            'timeout' => 10.0,
        ]);

        try {
            $req = $this->client->get('/');
            $this->status = $req->getStatusCode() === 200;
        } catch (\Exception $e) {
            LogService::warning(self::LOG_CHANNEL, "Connecting: " . $e->getMessage());
            $this->status = false;
        }
    }

    /**
     * Получает единственный экземпляр HttpService (singleton).
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function withRetryMiddleware(): HandlerStack
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::retry(
            function ($retries, RequestInterface $request, ResponseInterface $response = null, RequestException|ConnectException $exception = null) {
                if ($retries >= 3) return false;

                if ($exception instanceof ConnectException) return true;

                if ($exception instanceof RequestException) return true;

                if ($response && $response->getStatusCode() >= 500) {
                    LogService::warning(self::LOG_CHANNEL, "[HTTP {$response->getStatusCode()}] - Retry to connect {$request->getUri()}");
                    return true;
                }

                return false;
            },
            function ($retries) {
                return 1000 * $retries;
            }
        ));

        return $stack;
    }

    private function response(bool $success, string $message = '', array $data = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Выполняет HTTP-запрос с заданным методом, URI и параметрами.
     *
     * @param string $method HTTP-метод (GET, POST и т.д.)
     * @param string $uri Относительный URI запроса.
     * @param array $options Опции Guzzle (query, json, headers и т.д.)
     * @return array{success: bool, message: string, data: array} Ответ в унифицированной структуре
     */
    public function request(string $method, string $uri, array $options = [], bool $json_decode = true): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $message = "Request Error {$statusCode}: {$response->getReasonPhrase()}";
                LogService::error(self::LOG_CHANNEL, $message);

                return $this->response(false, $message);
            }

            if ($json_decode) return $this->response(true, '', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));

            return $this->response(true, $response->getBody()->getContents());

        } catch (ConnectException $e) {
            $host = $e->getRequest()->getHeader('Host')[0] ?? 'is NULL';
            $message = "[ConnectException] - Could not resolve host {$host}";
            LogService::error(self::LOG_CHANNEL, $message);
            return $this->response(false, $message);

        } catch (ClientException $e) {
            $message = "[ClientException] - ({$e->getCode()}) - ";

            $message .= match ($e->getCode()) {
                401 => "Error authenticating, check is set in EnvironmentService auth key.",
                404 => "$method {$e->getRequest()->getUri()} path not found.",
                default => $e->getMessage(),
            };

            LogService::error(self::LOG_CHANNEL, $message);
            return $this->response(false, $message);

        } catch (ServerException $e) {
            $message = "[ServerException] - ({$e->getCode()}) - ";

            $message .= match ($e->getCode()) {
                500 => "$method {$e->getRequest()->getUri()} internal server error.",
                default => $e->getMessage(),
            };

            LogService::error(self::LOG_CHANNEL, $message);
            return $this->response(false, $message);

        } catch (RequestException $e) {
            $message = "[RequestException] - ({$e->getCode()}) - {$e->getMessage()}";
            LogService::error(self::LOG_CHANNEL, $message);
            return $this->response(false, $message);

        } catch (JsonException $e) {
            $message = "[JsonException] - {$e->getMessage()}";
            LogService::error(self::LOG_CHANNEL, $message);
            return $this->response(false, $message);

        } catch (Throwable $e) {
            $message = "[Throwable] - {$e->getMessage()}";
            LogService::error(self::LOG_CHANNEL, $message);
            return $this->response(false, $message);

        }
    }

    /**
     * Выполняет GET-запрос.
     *
     * @param string $uri Относительный URI.
     * @param array $query Ассоциативный массив query-параметров.
     * @return array{success: bool, message: string, data: array}
     */
    public function get(string $uri, array $query = []): array
    {
        return $this->request('GET', $uri, ['query' => $query]);
    }

    /**
     * Выполняет POST-запрос.
     *
     * @param string $uri Относительный URI.
     * @param array $data Данные, отправляемые в теле запроса (формат JSON).
     * @return array{success: bool, message: string, data: array}
     */
    public function post(string $uri, array $data = []): array
    {
        return $this->request('POST', $uri, ['json' => $data]);
    }
}