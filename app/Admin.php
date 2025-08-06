<?php declare(strict_types=1);

namespace SGWPlugin;

use SGWClient;
use SGWPlugin\Classes\Environment;
use SGWPlugin\Classes\Fields;

if (!defined("ABSPATH")) die;

class Admin
{
    const PAGE_SLUG = 'sgw-plugin';

    function __construct()
    {
        add_action('acf/init', [$this, 'addOptionsPage']);
        add_filter('acf/settings/save_json/key=group_6830864c1fde5', [$this, 'saveFields']);
        add_filter('acf/settings/load_json', [$this, 'loadFields']);
        add_action('admin_init', [$this, 'addMetaboxes']);
    }

    public function addOptionsPage(): void
    {
        acf_add_options_page([
            'page_title' => 'SGW Plugin',
            'menu_slug' => self::PAGE_SLUG,
            'position' => '',
            'redirect' => false,
        ]);
    }

    public function saveFields($path): string
    {
        return SGWPLUGIN_PATH_ACF_JSON;
    }

    public function loadFields($paths): array
    {
        $paths[] = SGWPLUGIN_PATH_ACF_JSON;
        return $paths;
    }

    public function addMetaboxes(): void
    {
        if (isset($_GET['page']) && $_GET['page'] !== self::PAGE_SLUG) return;

        add_meta_box(
            'sgw-plugin-metabox-status',
            "SGW Plugin: Status",
            [$this, 'metaboxStatusHTML'],
            'acf_options_page',
            'side'
        );
    }

    public function metaboxStatusHTML(): void
    {
        $sgw = SGWClient::getInstance();

        $cacheStatus = $sgw->cache->status;
        $httpStatus = $sgw->http->status;
        $apiStatus = $sgw->api->status;
        $routingStatus = Fields::get_general_enable_matchcenter() && Fields::get_general_url_catalog_page();
        $updatesStatus = Environment::get("UPDATE_STATUS");

        echo $this->getBlock('General', [
            ['title' => 'Routing', 'content' => $this->getMessage($routingStatus, $routingStatus ? 'enabled' : 'disabled')]
        ]);

        echo $this->getBlock('SGW Client', [
            ['title' => 'Cache', 'content' => $this->getMessage((bool)$cacheStatus, $cacheStatus ?: 'not cache ext')],
            ['title' => 'HTTP', 'content' => $this->getMessage($httpStatus, $httpStatus ? 'connected' : 'disconnected')],
            ['title' => 'API', 'content' => $this->getMessage($apiStatus, $apiStatus ? 'connected' : 'disconnected')]
        ]);

        echo $this->getBlock('Updates', [
            ['title' => 'Git Repo', 'content' => $this->getMessage($updatesStatus, $updatesStatus ? 'connected' : 'disconnected')]
        ]);
    }

    private function getBlock(string $title, array $items): string
    {
        $html = "<h2 style='text-align: center'><strong>$title</strong></h2>";

        $html .= "<div style='display: flex; flex-direction: column; gap: 10px'>";

        foreach ($items as $item) {
            $html .= "<div style='display: flex; align-items: center;'>";
            $html .= "<strong>" . $item['title'] . "</strong>";
            $html .= "<div style='flex: 1; border: 1px dashed gray; margin: 0 10px'></div>";
            $html .= $item['content'];
            $html .= "</div>";
        }

        $html .= "</div>";

        return $html;
    }

    private function getMessage($status, $text): string
    {
        return sprintf('<span style="color: %s">%s</span>', $status ? 'green' : 'red', $text);
    }
}



