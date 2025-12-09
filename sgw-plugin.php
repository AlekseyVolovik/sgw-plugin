<?php declare(strict_types=1);

/**
 * Plugin Name:       SGW Plugin
 * Description:       Sportsgateway Plugin
 * Version:           1.1.17
 * Author:            AM
 * Text Domain:       sgw-plugin
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

use SGWPlugin\Admin;
use SGWPlugin\Routing;
use SGWPlugin\Utils;
use SGWPlugin\Shortcodes\CatalogShortcode;
use SGWPlugin\Classes\MetaBuilder;

// === 0) БАЗОВЫЕ КОНСТАНТЫ — СРАЗУ, ДО ЛЮБЫХ include ===
if (!defined('SGWPLUGIN_PATH'))         define('SGWPLUGIN_PATH', plugin_dir_path(__FILE__));
if (!defined('SGWPLUGIN_FILE'))         define('SGWPLUGIN_FILE', __FILE__);
if (!defined('SGWPLUGIN_URL'))          define('SGWPLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('SGWPLUGIN_PATH_TEMPLATES')) define('SGWPLUGIN_PATH_TEMPLATES', SGWPLUGIN_PATH . 'templates');
if (!defined('SGWPLUGIN_PATH_BACK'))      define('SGWPLUGIN_PATH_BACK', SGWPLUGIN_PATH . 'mc-back');
if (!defined('SGWPLUGIN_PATH_FRONT'))     define('SGWPLUGIN_PATH_FRONT', SGWPLUGIN_PATH . 'mc-front');
if (!defined('SGWPLUGIN_PATH_FLAGS'))     define('SGWPLUGIN_PATH_FLAGS', SGWPLUGIN_PATH . 'mc-flags');
if (!defined('SGWPLUGIN_PATH_ACF_JSON'))  define('SGWPLUGIN_PATH_ACF_JSON', SGWPLUGIN_PATH . 'acf-json');
if (!defined('SGWPLUGIN_URL_FRONT'))      define('SGWPLUGIN_URL_FRONT', SGWPLUGIN_URL . 'mc-front');
if (!defined('SGWPLUGIN_URL_FLAGS'))      define('SGWPLUGIN_URL_FLAGS', SGWPLUGIN_URL . 'mc-flags');
// Наборы тем
if (!defined('SGWPLUGIN_PATH_THEMES'))    define('SGWPLUGIN_PATH_THEMES', SGWPLUGIN_PATH . 'themes');
if (!defined('SGWPLUGIN_URL_THEMES'))     define('SGWPLUGIN_URL_THEMES',  SGWPLUGIN_URL  . 'themes');

// === 1) АВТОЛОАДЕР (ОДИН РАЗ) ===
$autoload = SGWPLUGIN_PATH . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Если нет композера для шорткодов — подключаем файл ТОЛЬКО ПОСЛЕ констант и автолоадера:
require_once SGWPLUGIN_PATH . 'shortcodes/CatalogShortcode.php';

// === 2) ВСПОМОГАТЕЛЬНЫЕ include WP ===
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// === 3) ПЛАГИН ===
final class SGWPluginMain {
    public function __construct() {
        $this->hooks();
    }

    private function hooks(): void {
        register_activation_hook(SGWPLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(SGWPLUGIN_FILE, [$this, 'deactivate']);

        add_action('plugins_loaded', [$this, 'boot']);
    }

    public function boot(): void {
        if (!$this->requires()) {
            return;
        }

        new Utils();
        new Admin();
        new Routing();

        CatalogShortcode::register();

        // meta builder
        add_action('wp_head', ['\\SGWPlugin\\Classes\\MetaBuilder', 'output'], 1);

        // отключим Yoast на динамических страницах
        add_action('wp', function () {
            if (\SGWPlugin\Classes\MetaBuilder::isDynamicPage()) {
                remove_all_actions('wpseo_head');
            }
        }, 0);
    }

    private function requires(): bool {
        $ok = true;
        if (!file_exists(SGWPLUGIN_PATH_BACK)) {
            add_action('admin_head', fn() => wp_admin_notice('SGW Plugin: SGW Backend not installed.', ['type' => 'error']));
            $ok = false;
        }
        if (!file_exists(SGWPLUGIN_PATH_FRONT)) {
            add_action('admin_head', fn() => wp_admin_notice('SGW Plugin: SGW Frontend not installed.', ['type' => 'error']));
            $ok = false;
        }
        if (!file_exists(SGWPLUGIN_PATH_FLAGS)) {
            add_action('admin_head', fn() => wp_admin_notice('SGW Plugin: SGW team flags not installed.', ['type' => 'error']));
            $ok = false;
        }
        if (!is_plugin_active('advanced-custom-fields-pro/acf.php')) {
            add_action('admin_head', fn() => wp_admin_notice('SGW Plugin: ACF Pro plugin not installed.', ['type' => 'error']));
            $ok = false;
        }
        return $ok;
    }

    public function activate(): void { flush_rewrite_rules(); }
    public function deactivate(): void { flush_rewrite_rules(); }
}

// === 4) ОБНОВЛЕНИЯ PUC (после констант/автолоадера) ===
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
if (class_exists(PucFactory::class)) {
    PucFactory::buildUpdateChecker(
        'https://github.com/AlekseyVolovik/sgw-plugin/',
        SGWPLUGIN_FILE,
        'sgw-plugin'
    );
}

// === 5) ЗАПУСК ===
new SGWPluginMain();
