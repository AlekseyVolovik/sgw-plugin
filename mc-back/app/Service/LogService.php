<?php declare(strict_types=1);

namespace SGW\Service;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;


/**
 * Class LogService
 *
 * Сервис логирования с поддержкой нескольких каналов.
 * Использует Monolog и сохраняет логгеры по имени канала, чтобы избежать повторного создания объектов.
 *
 * Позволяет вызывать статические методы логирования:
 *      <code>
 *          LogService::info('http', 'Сообщение', [...]);
 *      </code>
 *
 * Каждый канал логируется в отдельный файл: {SGW_PATH_LOGS}/<channel>.log
 *
 * @package Sportsgateway\Service
 */
class LogService
{
    private static array $instances = [];

    private function __construct()
    {
    }

    private static function getLogger(string $channel): Logger
    {
        if (!isset(self::$instances[$channel])) {
            $logger = new Logger($channel);

            $logFile = SGW_PATH_LOGS . "$channel.log";

            $handler = new StreamHandler($logFile, Logger::DEBUG);
            $handler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            ));

            $logger->pushHandler($handler);
            self::$instances[$channel] = $logger;
        }

        return self::$instances[$channel];
    }

    /**
     * Логирует сообщение уровня DEBUG.
     *
     * @param string $channel Название лог-файла (канал).
     * @param string $message Сообщение для логирования.
     * @param array $context Контекст для подстановки в сообщение.
     * @return void
     */
    public static function debug(string $channel, string $message, array $context = []): void
    {
        self::getLogger($channel)->debug($message, $context);
    }

    /**
     * Логирует сообщение уровня INFO.
     *
     * @param string $channel Название лог-файла (канал).
     * @param string $message Сообщение для логирования.
     * @param array $context Контекст для подстановки в сообщение.
     * @return void
     */
    public static function info(string $channel, string $message, array $context = []): void
    {
        self::getLogger($channel)->info($message, $context);
    }

    /**
     * Логирует сообщение уровня WARNING.
     *
     * @param string $channel Название лог-файла (канал).
     * @param string $message Сообщение для логирования.
     * @param array $context Контекст для подстановки в сообщение.
     * @return void
     */
    public static function warning(string $channel, string $message, array $context = []): void
    {
        self::getLogger($channel)->warning($message, $context);
    }

    /**
     * Логирует сообщение уровня ERROR.
     *
     * @param string $channel Название лог-файла (канал).
     * @param string $message Сообщение для логирования.
     * @param array $context Контекст для подстановки в сообщение.
     * @return void
     */
    public static function error(string $channel, string $message, array $context = []): void
    {
        self::getLogger($channel)->error($message, $context);
    }
}