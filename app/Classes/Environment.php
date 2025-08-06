<?php declare(strict_types=1);

namespace SGWPlugin\Classes;
/**
 * Простая реализация переменных окружения с let и const
 */
class Environment
{
    private static ?self $instance = null;
    private array $let = [];
    private array $const = [];

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private static function ensureInstance(): void
    {
        if (self::$instance === null) self::$instance = new self();
    }

    /**
     * Устанавливает переменную в let или const
     *
     * @param string $key Ключ
     * @param mixed $value Значение
     * @param bool $isConst true — const, false — let
     *
     * @return bool true если значение установлено, false если const уже существует
     */
    public static function set(string $key, mixed $value, bool $isConst = false): bool
    {
        self::ensureInstance();

        if ($isConst) {
            if (array_key_exists($key, self::$instance->const)) return false;

            self::$instance->const[$key] = $value;
            return true;
        }

        self::$instance->let[$key] = $value;
        return true;
    }

    /**
     * Получает переменную по ключу
     */
    public static function get(string $key): mixed
    {
        self::ensureInstance();

        return self::$instance->const[$key]
            ?? self::$instance->let[$key]
            ?? null;
    }

    /**
     * Удаляет let-переменную
     */
    public static function remove(string $key): bool
    {
        self::ensureInstance();

        if (array_key_exists($key, self::$instance->let)) {
            unset(self::$instance->let[$key]);
            return true;
        }

        return false;
    }

    /**
     * Возвращает все переменные (const + let)
     */
    public static function getAll(): array
    {
        self::ensureInstance();
        return array_merge(self::$instance->const, self::$instance->let);
    }

    /**
     * Проверяет, является ли переменная const
     */
    public static function isConst(string $key): bool
    {
        self::ensureInstance();
        return array_key_exists($key, self::$instance->const);
    }
}