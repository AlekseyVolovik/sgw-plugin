<?php declare(strict_types=1);

namespace SGWPlugin\Theme;

if (!defined('ABSPATH')) exit;

final class ThemeManager
{
    /** Разрешённые темы плагина */
    private const ALLOWED = ['classic', 'platina'];

    /** Ключ опции: используем твоё поле ACF select */
    private const OPTION_KEY = 'sgw_theme';

    /** Дефолтная тема */
    private const DEFAULT = 'classic';

    /** Текущая активная тема (кэш в рамках запроса) */
    private static ?string $active = null;

    /**
     * Получить активную тему:
     * - ACF Options (поле select 'sgw_theme')
     * - wp_options (fallback)
     * - 'classic' по умолчанию
     */
    public static function getActive(): string
    {
        if (self::$active !== null) {
            return self::$active;
        }

        $value = null;

        // 1) ACF Options (оба контейнера встречаются: 'option' и 'options')
        if (function_exists('get_field')) {
            $v = get_field(self::OPTION_KEY, 'option'); 
            if ($v === null || $v === '' || $v === false) {
                $v = get_field(self::OPTION_KEY, 'options');
            }
            // В зависимости от типа поля в ACF:
            // - select (return = value): строка
            // - select (return = array): ['value' => 'classic', 'label' => 'Classic']
            if (is_string($v) && $v !== '') {
                $value = $v;
            } elseif (is_array($v) && !empty($v['value'])) {
                $value = (string) $v['value'];
            }
        }

        // 2) wp_options fallback
        if ($value === null) {
            $opt = get_option(self::OPTION_KEY);
            if (is_string($opt) && $opt !== '') {
                $value = $opt;
            }
        }

        // 3) sanitize + whitelist
        $value = is_string($value) ? strtolower(trim($value)) : '';
        if (!in_array($value, self::ALLOWED, true)) {
            $value = self::DEFAULT;
        }

        self::$active = $value;
        return self::$active;
    }

    /** Базовый дисковый путь к каталогу тем плагина */
    private static function basePath(): string
    {
        // Важно: константы без завершающих слэшей
        $base = defined('SGWPLUGIN_PATH_THEMES')
            ? SGWPLUGIN_PATH_THEMES
            : plugin_dir_path(__FILE__) . '../../themes';

        return rtrim($base, "/\\");
    }

    /** Базовый URL к каталогу тем плагина */
    private static function baseUrl(): string
    {
        $base = defined('SGWPLUGIN_URL_THEMES')
            ? SGWPLUGIN_URL_THEMES
            : plugins_url('themes', dirname(__FILE__, 2));

        return rtrim($base, "/\\");
    }

    /** Абсолютный путь к активной теме */
    public static function path(string $rel = ''): string
    {
        $rel = ltrim($rel, "/\\");
        return self::basePath() . '/' . self::getActive() . ($rel ? '/' . $rel : '');
    }

    /** URL к активной теме */
    public static function url(string $rel = ''): string
    {
        $rel = ltrim($rel, "/\\");
        return self::baseUrl() . '/' . self::getActive() . ($rel ? '/' . $rel : '');
    }

    /**
     * URL ассета внутри темы.
     * Автодобавляем префикс `front/` если его нет.
     *
     * Примеры вызова:
     *  - assetUrl('css/app.css')      → themes/<active>/front/css/app.css
     *  - assetUrl('front/js/app.js')  → themes/<active>/front/js/app.js
     */
    public static function assetUrl(string $relative): string
    {
        $relative = ltrim($relative, "/\\");
        if (strpos($relative, 'front/') !== 0) {
            $relative = 'front/' . $relative;
        }
        return self::url($relative);
    }

    /** Абсолютный путь к любому файлу темы (напр. templates/...) */
    public static function templatePath(string $relative): string
    {
        $relative = ltrim($relative, "/\\");
        return self::path($relative);
    }

    /**
     * Пути для Twig: сначала активная тема (views/templates/twig),
     * затем — директории ядра плагина (если есть).
     */
    public static function twigViews(): array
    {
        $paths = [];

        $theme = self::path();
        foreach (['views', 'templates', 'twig'] as $sub) {
            $dir = $theme . '/' . $sub;
            if (is_dir($dir)) {
                $paths[] = $dir;
            }
        }

        // Фолбэки ядра
        if (defined('SGWPLUGIN_PATH_TEMPLATES') && is_dir(SGWPLUGIN_PATH_TEMPLATES)) {
            $paths[] = SGWPLUGIN_PATH_TEMPLATES;
        }
        $coreViews = dirname(__DIR__) . '/Views';
        if (is_dir($coreViews)) {
            $paths[] = $coreViews;
        }

        return array_values(array_unique($paths));
    }
}
