<?php

namespace SGWPlugin\Shortcodes;

use SGWPlugin\Controllers\CatalogController;

class CatalogShortcode
{
    public static function register(): void
    {
        add_shortcode('football_catalog', [self::class, 'render']);
    }

    public static function render($atts = []): string
    {
        // Подключаем стили и скрипты только при рендере шорткода
        wp_enqueue_style('mcstyle', SGWPLUGIN_URL_FRONT . '/css/app.css');
        wp_enqueue_script('mcscript', SGWPLUGIN_URL_FRONT . '/js/app.js', [], false, true);
        wp_enqueue_style('sgw-plugin-styles', SGWPLUGIN_URL_FRONT . '/fonts/roboto/style.css');

        $atts = shortcode_atts([
            'entry'  => 'football',
            'status' => null,
            'period' => null,
            'date'   => null,
        ], $atts);

        $controller = new CatalogController($atts);
        $content    = $controller->render() ?? '';

        // Оборачиваем в требуемый section
        return sprintf(
            '<section id="post-sgw-catalog" class="post-sgw-catalog">%s</section>',
            $content
        );
    }
}
