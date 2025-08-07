<?php declare(strict_types=1);

use SGWPlugin\Utils;
use SGWPlugin\Admin;
use SGWPlugin\Routing;
use SGWPlugin\Shortcodes\CatalogShortcode;
use SGWPlugin\Classes\MetaBuilder;

if (!defined("ABSPATH")) die;

/**
 * Plugin Name:       SGW Plugin
 * Description:       Sportsgateway Plugin
 * Version:           1.1.2
 * Author:            AM
 * Text Domain:       sgw-plugin
 * Domain Path:       /languages
 */
final class SGWPlugin
{
    function __construct()
    {
        $this->defines();
        $this->bootstrap();
        $this->setup();
    }

    private function defines(): void
    {
        define('SGWPLUGIN_PATH', plugin_dir_path(__FILE__));
        define('SGWPLUGIN_PATH_INDEX', __FILE__);
        define('SGWPLUGIN_PATH_TEMPLATES', SGWPLUGIN_PATH . 'templates');
        define('SGWPLUGIN_PATH_BACK', SGWPLUGIN_PATH . 'mc-back');
        define('SGWPLUGIN_PATH_FRONT', SGWPLUGIN_PATH . 'mc-front');
        define('SGWPLUGIN_PATH_FLAGS', SGWPLUGIN_PATH . 'mc-flags');
        define('SGWPLUGIN_PATH_ACF_JSON', SGWPLUGIN_PATH . 'acf-json');

        define('SGWPLUGIN_URL', plugin_dir_url(__FILE__));
        define('SGWPLUGIN_URL_FRONT', SGWPLUGIN_URL . 'mc-front');
        define('SGWPLUGIN_URL_FLAGS', SGWPLUGIN_URL . 'mc-flags');
    }

    private function bootstrap(): void
    {
        # Composer autoload
        require 'vendor/autoload.php';

        # Need to func is_plugin_active
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        # Base plugin hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    private function requires(): bool
    {
        $errors = [];

        if (!file_exists(SGWPLUGIN_PATH_BACK)) {
            add_action('admin_head', function () {
                wp_admin_notice('SGW Plugin: SGW Backend not installed.', ['type' => 'error']);
            });
            $errors[] = true;
        }

        if (!file_exists(SGWPLUGIN_PATH_FRONT)) {
            add_action('admin_head', function () {
                wp_admin_notice('SGW Plugin: SGW Frontend not installed.', ['type' => 'error']);
            });
            $errors[] = true;
        }

        if (!file_exists(SGWPLUGIN_PATH_FLAGS)) {
            add_action('admin_head', function () {
                wp_admin_notice('SGW Plugin: SGW team flags not installed.', ['type' => 'error']);
            });
            $errors[] = true;
        }

        if (!is_plugin_active('advanced-custom-fields-pro/acf.php')) {
            add_action('admin_head', function () {
                wp_admin_notice('SGW Plugin: ACF Pro plugin not installed.', ['type' => 'error']);
            });
            $errors[] = true;
        }

        return !in_array(true, $errors);
    }

    public function setup(): void
    {
        if (!$this->requires()) return;

        new Utils();
        new Admin();
        new Routing();

        CatalogShortcode::register();

        add_action('wp_head', ['\\SGWPlugin\\Classes\\MetaBuilder', 'output'], 1);

        // Отключение использования yoast seo на динамических страницах
        add_action('wp', function () {
            if (\SGWPlugin\Classes\MetaBuilder::isDynamicPage()) {
                remove_all_actions('wpseo_head');
            }
        }, 0);
    }

    public function activate(): void
    {
        flush_rewrite_rules();
    }

    public function deactivate(): void
    {
        flush_rewrite_rules();
    }
}

require_once __DIR__ . '/shortcodes/CatalogShortcode.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
require_once __DIR__ . '/vendor/autoload.php';
$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/AlekseyVolovik/sgw-plugin/', // ✅ / на конце
    __FILE__,
    'sgw-plugin'
);
$updateChecker->getVcsApi()->enableReleaseAssets();

new SGWPlugin();
