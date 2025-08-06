<?php declare(strict_types=1);

namespace SGWPlugin;

use Routes;
use SGWPlugin\Classes\Fields;

if (!defined("ABSPATH")) die;

class Routing
{
    private bool $isEnableMatchcenter;
    private ?string $baseUrlCatalog;

    public function __construct()
    {
        add_action('acf/init', [$this, 'init']);
    }

    public function init(): void {
        $this->isEnableMatchcenter = Fields::get_general_enable_matchcenter();
        $this->baseUrlCatalog = Fields::get_general_url_catalog_page();

        if (!$this->isEnableMatchcenter || !$this->baseUrlCatalog) return;

        // Базовая страница: /football
        $this->addRoute("[$this->baseUrlCatalog:entry]", SGWPLUGIN_PATH_TEMPLATES . '/catalog.php');

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
            });

            Routes::load($templatePath, $params);
        });
    }
}
