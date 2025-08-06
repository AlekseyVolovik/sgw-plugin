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
        if(!is_admin()) return;

        $gitRepo = Fields::get_updates_git_repository();
        $gitToken = Fields::get_updates_git_token();

        Environment::set('UPDATE_STATUS', false);

        if (!$gitRepo || !$gitToken) return;

        $update_checker = PucFactory::buildUpdateChecker($gitRepo, SGWPLUGIN_PATH_INDEX, 'sgw-plugin');
        $update_checker->getVcsApi()->enableReleaseAssets('/^sgw-plugin\.zip$/');
        $update_checker->setBranch('main');
        $update_checker->setAuthentication($gitToken);

        // TODO: Запрос жрет время загрузки, мб отказаться от него, или найти способ оптимизировать
        Environment::set('UPDATE_STATUS', (bool)$update_checker->requestInfo());
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