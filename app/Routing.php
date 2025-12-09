<?php declare(strict_types=1);

namespace SGWPlugin;

use Routes;
use SGWPlugin\Classes\Fields;
use SGWPlugin\Theme\ThemeManager;

if (!defined('ABSPATH')) die;

class Routing
{
    private bool $isEnableMatchcenter = false;
    private ?string $baseUrlCatalog = null;
    private bool $isEnableMatchPages = false;

    public function __construct()
    {
        /**
         * Роутинг нельзя вешать на acf/init — это слишком поздно и не гарантировано для фронта.
         * Делаем на 'init' с низким приоритетом, когда WP уже готов, ACF опции доступны.
         */
        add_action('init', [$this, 'init'], 0);

        /**
         * Защита от “висящих” матч-URL при выключенных страницах матча.
         * Делаем это на template_redirect, но с учётом сайта в подпапке.
         */
        add_action('template_redirect', [$this, 'maybeForce404ForMatch'], 0);
    }

    public function init(): void
    {
        // Загружаем флаги и базу только один раз
        $this->isEnableMatchcenter = (bool) Fields::get_general_enable_matchcenter();
        $this->baseUrlCatalog      = Fields::get_general_url_catalog_page() ?: null;
        $this->isEnableMatchPages  = (bool) Fields::get_general_enable_match_pages();

        // Если нет каталога или выключен matchcenter — дальше роуты не добавляем
        if (!$this->isEnableMatchcenter || !$this->baseUrlCatalog) {
            return;
        }

        // Если библиотека Routes не активна — выходим тихо (чтобы не было фатала)
        if (!class_exists('\Routes')) {
            return;
        }

        // Базовая: /football
        $this->addRoute(
            sprintf('[%s:entry]', $this->baseUrlCatalog),
            SGWPLUGIN_PATH_TEMPLATES . '/catalog.php'
        );

        // Тестовая: /football/test
        $this->addRoute(
            sprintf('[%s:entry]/test', $this->baseUrlCatalog),
            SGWPLUGIN_PATH_TEMPLATES . '/test-events.php'
        );

        // Страница матча: /football/match/...
        if ($this->isEnableMatchPages) {
            $this->addRoute(
                sprintf('[%s:entry]/match/[:slug]', $this->baseUrlCatalog),
                SGWPLUGIN_PATH_TEMPLATES . '/match-view.php'
            );
        }

        // Периоды: /football/today|tomorrow|yesterday
        $this->addRoute(
            sprintf('[%s:entry]/[today|tomorrow|yesterday:period]', $this->baseUrlCatalog),
            SGWPLUGIN_PATH_TEMPLATES . '/catalog.php'
        );

        // Статусы: /football/live|upcoming|finished
        $this->addRoute(
            sprintf('[%s:entry]/[live|upcoming|finished:status]', $this->baseUrlCatalog),
            SGWPLUGIN_PATH_TEMPLATES . '/catalog.php'
        );

        // Календарь: /football/upcoming|finished/2025-07-21
        $this->addRoute(
            sprintf('[%s:entry]/[upcoming|finished:status]/[:date]', $this->baseUrlCatalog),
            SGWPLUGIN_PATH_TEMPLATES . '/catalog.php'
        );

        // Страна
        $this->addRoute(
            sprintf('[%s:entry]/[:country]', $this->baseUrlCatalog),
            SGWPLUGIN_PATH_TEMPLATES . '/country-view.php'
        );

        // Страна/лига
        $this->addRoute(
            sprintf('[%s:entry]/[:country]/[:league]', $this->baseUrlCatalog),
            SGWPLUGIN_PATH_TEMPLATES . '/league-view.php'
        );
    }

    /**
     * Принудительно отдаём 404 на /{catalog}/match/*, если матч-страницы выключены.
     * Учитываем сайт в подпапке.
     */
    public function maybeForce404ForMatch(): void
    {
        if (!$this->baseUrlCatalog) {
            return;
        }

        if ($this->isEnableMatchPages) {
            return; // включены — ничего не делаем
        }

        // Текущий относительный путь запроса
        $reqUri = $_SERVER['REQUEST_URI'] ?? '/';

        // База сайта (если WP в подпапке)
        $sitePath = wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/';
        $sitePath = rtrim($sitePath, '/');

        // Ищем префикс /{sitePath}/{catalog}/match/
        $prefix = '/' . ltrim($this->baseUrlCatalog, '/') . '/match/';
        $fullPrefix = ($sitePath === '' ? '' : $sitePath) . $prefix;

        if (stripos($reqUri, $fullPrefix) === 0) {
            // Отдаём 404 и выходим
            global $wp_query;
            if (function_exists('status_header')) {
                status_header(404);
            }
            if (function_exists('nocache_headers')) {
                nocache_headers();
            }
            if (isset($wp_query)) {
                $wp_query->set_404();
            }
            // рендерим тему 404, если есть
            $template404 = get_404_template();
            if ($template404) {
                include $template404;
            } else {
                // минимальный фолбэк
                include get_query_template('index');
            }
            exit;
        }
    }

    private function addRoute(string $pattern, string $templatePath): void
    {
        // На всякий пожарный — не грузим несуществующий файл
        if (!is_file($templatePath)) {
            return;
        }

        Routes::map($pattern, function ($params) use ($templatePath) {
            add_action('wp_enqueue_scripts', function () {
                \SGWPlugin\Theme\Assets::enqueue();
            });

            // Рендерим «ядровый» шаблон — внутри он сам подтянет нужные partials через Twig и ThemeManager
            Routes::load($templatePath, $params);
        });
    }
}
