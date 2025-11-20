<?php declare(strict_types=1);

namespace SGWPlugin;

use Routes;
use SGWPlugin\Classes\Fields;

if (!defined("ABSPATH")) die;

class Routing
{
    private bool $isEnableMatchcenter;
    private ?string $baseUrlCatalog;
    private bool $isEnableMatchPages;

    public function __construct()
    {
        add_action('acf/init', [$this, 'init']);
    }

    public function init(): void {
        $this->isEnableMatchcenter = Fields::get_general_enable_matchcenter();
        $this->baseUrlCatalog = Fields::get_general_url_catalog_page();
        $this->isEnableMatchPages  = \SGWPlugin\Classes\Fields::get_general_enable_match_pages();

        // Показ страницы 404 при отключении страниц матчей
        add_action('template_redirect', function () {
            if (!$this->baseUrlCatalog) return;

            if (!\SGWPlugin\Classes\Fields::get_general_enable_match_pages()) {
                $req = $_SERVER['REQUEST_URI'] ?? '';
                $prefix = '/' . trim($this->baseUrlCatalog, '/') . '/match/';
                if (stripos($req, $prefix) === 0) {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header(404);
                    nocache_headers();
                    include get_404_template();
                    exit;
                }
            }
        });

        if (!$this->isEnableMatchcenter || !$this->baseUrlCatalog) return;

        // Базовая страница: /football
        $this->addRoute("[$this->baseUrlCatalog:entry]", SGWPLUGIN_PATH_TEMPLATES . '/catalog.php');

        // Тестовая страница: /football/test
        $this->addRoute("[$this->baseUrlCatalog:entry]/test", SGWPLUGIN_PATH_TEMPLATES . '/test-events.php');

        // Cтраница матча: /match/...
        if ($this->isEnableMatchPages) {
            $this->addRoute("[$this->baseUrlCatalog:entry]/match/[:slug]", SGWPLUGIN_PATH_TEMPLATES . '/match-view.php');
        }

        // Вкладки-периоды: /football/today, /football/yesterday, /football/tomorrow
        $this->addRoute("[$this->baseUrlCatalog:entry]/[today|tomorrow|yesterday:period]", SGWPLUGIN_PATH_TEMPLATES . '/catalog.php');

        // Вкладки-статусы: /football/live, /football/upcoming, /football/finished
        $this->addRoute("[$this->baseUrlCatalog:entry]/[live|upcoming|finished:status]", SGWPLUGIN_PATH_TEMPLATES . '/catalog.php');

        // Календарь: /football/upcoming/2025-07-21
        $this->addRoute("[$this->baseUrlCatalog:entry]/[upcoming|finished:status]/[:date]", SGWPLUGIN_PATH_TEMPLATES . '/catalog.php');

        $this->addRoute("[$this->baseUrlCatalog:entry]/[:country]", SGWPLUGIN_PATH_TEMPLATES . '/country-view.php');

        $this->addRoute("[$this->baseUrlCatalog:entry]/[:country]/[:league]", SGWPLUGIN_PATH_TEMPLATES . '/league-view.php');
    }

    private function addRoute(string $pattern, string $templatePath): void
    {
        Routes::map($pattern, function ($params) use ($templatePath) {
            add_action('wp_enqueue_scripts', function () {
                wp_enqueue_style('mcstyle', SGWPLUGIN_URL_FRONT . '/css/app.css');
                wp_enqueue_script('mcscript', SGWPLUGIN_URL_FRONT . '/js/app.js', [], false, true);
                wp_enqueue_style('sgw-plugin-styles', SGWPLUGIN_URL_FRONT . '/fonts/roboto/style.css');
            });

            Routes::load($templatePath, $params);
        });
    }
}
