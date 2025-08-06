<?php declare(strict_types=1);

namespace SGWPlugin\Classes;

if (!defined("ABSPATH")) die;

class Fields
{
    public static function get_options_field(string $acf_key): mixed
    {
        return get_field($acf_key, 'options');
    }

    public static function get_general_enable_matchcenter(): bool
    {
        return (bool)self::get_options_field('sgw_plugin_general_enable_match_center');
    }

    public static function get_general_project_id(): ?int
    {
        $field = self::get_options_field('sgw_plugin_general_project_id');
        return is_numeric($field) ? (int)$field : null;
    }

    public static function get_general_sport(): string
    {
        return self::get_options_field('sgw_plugin_general_sport') ?? '';
    }

    public static function get_general_url_catalog_page(): string
    {
        return self::get_options_field('sgw_plugin_general_url_catalog_page') ?? '';
    }

    public static function get_sgwclient_base_url(): string
    {
        return self::get_options_field('sgw_plugin_sgwclient_base_url') ?? '';
    }

    public static function get_sgwclient_base_auth(): string
    {
        return self::get_options_field('sgw_plugin_sgwclient_base_auth') ?? '';
    }

    public static function get_sgwclient_cache_host(): string
    {
        return self::get_options_field('sgw_plugin_sgwclient_cache_host') ?? '';
    }

    public static function get_sgwclient_cache_port(): ?int
    {
        $field = self::get_options_field('sgw_plugin_sgwclient_cache_port');
        return is_numeric($field) ? (int)$field : null;
    }

    public static function get_sgwclient_cache_expires(): ?int
    {
        $field = self::get_options_field('sgw_plugin_sgwclient_cache_expires');
        return is_numeric($field) ? (int)$field : null;
    }

    public static function get_updates_git_repository(): string
    {
        return self::get_options_field('sgw_plugin_updates_git_repository') ?? '';
    }

    public static function get_updates_git_token(): string
    {
        return self::get_options_field('sgw_plugin_updates_git_token') ?? '';
    }
}
