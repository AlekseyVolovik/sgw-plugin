<?php declare(strict_types=1);

namespace SGWPlugin;

use SGWClient;
use SGWPlugin\Classes\Environment;
use SGWPlugin\Classes\Fields;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if (!defined("ABSPATH")) die;

class Utils
{
    function __construct()
    {
        add_action('acf/init', [$this, 'autoupdate']);
        add_action('acf/init', [$this, 'sgwclient']);
    }

    public function autoupdate(): void
    {
        // работаем только в админке
        if (!is_admin()) {
            return;
        }

        // если PUC не подключен — просто выходим
        if (!class_exists(\YahnisElsts\PluginUpdateChecker\v5\PucFactory::class)) {
            return;
        }

        // Читаем настройки из ACF
        $gitRepo  = trim((string) Fields::get_updates_git_repository());
        $gitToken = trim((string) Fields::get_updates_git_token());

        // По умолчанию считаем, что соединения нет
        Environment::set('UPDATE_STATUS', false);

        // Если репо не задано — вообще не настраиваем апдейтер
        if ($gitRepo === '') {
            return;
        }

        // Создаём апдейтер для текущего плагина
        $update_checker = PucFactory::buildUpdateChecker(
            $gitRepo,
            SGWPLUGIN_FILE,  // <--- важно: путь к основному файлу плагина
            'sgw-plugin'
        );

        // Используем релизный ZIP с GitHub Actions (.release-plugin.yml)
        $update_checker->getVcsApi()->enableReleaseAssets('/^sgw-plugin\.zip$/');
        $update_checker->setBranch('main');

        // Токен опционален: если репо публичный, можно оставить пустым
        if ($gitToken !== '') {
            $update_checker->setAuthentication($gitToken);
        }

        // Чтобы не тормозить весь админ, проверяем соединение только на нашей странице настроек
        if (isset($_GET['page']) && $_GET['page'] === Admin::PAGE_SLUG) {
            try {
                $info = $update_checker->requestInfo();
                Environment::set('UPDATE_STATUS', (bool) $info);
            } catch (\Throwable $e) {
                Environment::set('UPDATE_STATUS', false);
            }
        }
    }

    public function sgwclient(): void
    {
        // TODO: не совсем нравится подключение через require, нужно сделать подключение через обращение к классу
        require SGWPLUGIN_PATH_BACK . '/index.php';

        SGWClient::create([
            'baseUrl' => Fields::get_sgwclient_base_url(),
            'baseAuth' => Fields::get_sgwclient_base_auth(),
            'cacheHost' => Fields::get_sgwclient_cache_host(),
            'cachePort' => Fields::get_sgwclient_cache_port(),
            'cacheExpires' => Fields::get_sgwclient_cache_expires(),
        ]);
    }
}