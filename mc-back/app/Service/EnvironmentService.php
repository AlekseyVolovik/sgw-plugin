<?php declare(strict_types=1);

namespace SGW\Service;

/**
 * Сервис для управления переменными окружения
 *
 * Предоставляет статические методы для хранения и доступа к переменным окружения
 * в рамках всего приложения. Реализует паттерн Singleton для гарантии единственного
 * экземпляра хранилища переменных.
 */
class EnvironmentService
{
    private static ?self $instance = null;
    private array $env = [];

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private static function ensureInstance(): void
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
    }

    /**
     * Устанавливает значение переменной окружения, если она еще не установлена
     *
     * @param string $key Ключ переменной
     * @param mixed $value Значение переменной
     * @return bool Возвращает true, если переменная была установлена, false если уже существовала
     */
    public static function set(string $key, mixed $value): bool
    {
        self::ensureInstance();

        if (!isset(self::$instance->env[$key])) {
            self::$instance->env[$key] = $value;
            return true;
        }

        return false;
    }

    /**
     * Получает значение переменной окружения
     *
     * @param string $key Ключ переменной
     * @return mixed Значение переменной или null, если переменная не существует
     */
    public static function get(string $key): mixed
    {
        self::ensureInstance();
        return self::$instance->env[$key] ?? null;
    }

    /**
     * Возвращает все переменные окружения
     *
     * @return array Ассоциативный массив всех переменных окружения
     */
    public static function getAll(): array
    {
        self::ensureInstance();
        return self::$instance->env;
    }

    /**
     * Удаляет переменную окружения
     *
     * @param string $key Ключ переменной для удаления
     * @return bool Возвращает true, если переменная была удалена, false если не существовала
     */
    public static function remove(string $key): bool
    {
        self::ensureInstance();

        if (array_key_exists($key, self::$instance->env)) {
            unset(self::$instance->env[$key]);
            return true;
        }

        return false;
    }
}