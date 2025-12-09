<?php declare(strict_types=1);

namespace SGWPlugin\Theme;

if (!defined('ABSPATH')) exit;

final class Assets
{
    public static function enqueue(): void
    {
        $theme = ThemeManager::getActive();

        // Вся статика лежит в /themes/<active>/front/...
        $relCss   = 'front/css/app.css';
        $relJs    = 'front/js/app.js';
        $relFonts = 'front/fonts/roboto/style.css';

        // URL для подключения
        $cssUrl   = ThemeManager::assetUrl($relCss);
        $jsUrl    = ThemeManager::assetUrl($relJs);
        $fontUrl  = ThemeManager::assetUrl($relFonts);

        // Абсолютные пути для версионирования
        $cssPath  = ThemeManager::templatePath($relCss);
        $jsPath   = ThemeManager::templatePath($relJs);
        $fontPath = ThemeManager::templatePath($relFonts);

        $vCss   = is_file($cssPath)  ? (string) filemtime($cssPath)  : null;
        $vJs    = is_file($jsPath)   ? (string) filemtime($jsPath)   : null;
        $vFont  = is_file($fontPath) ? (string) filemtime($fontPath) : null;

        $hCss   = "sgw-theme-{$theme}-css";
        $hJs    = "sgw-theme-{$theme}-js";
        $hFont  = "sgw-theme-{$theme}-font";

        // Регистрируем и подключаем
        wp_register_style($hCss,  $cssUrl,  [], $vCss);
        wp_register_style($hFont, $fontUrl, [], $vFont);
        wp_register_script($hJs,  $jsUrl,   [], $vJs, true);

        wp_enqueue_style($hFont);
        wp_enqueue_style($hCss);
        wp_enqueue_script($hJs);
    }
}
