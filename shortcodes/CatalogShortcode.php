<?php

namespace SGWPlugin\Shortcodes;

use SGWPlugin\Controllers\CatalogController;
use SGWPlugin\Theme\Assets;

class CatalogShortcode
{
    public static function register(): void
    {
        add_shortcode('football_catalog', [self::class, 'render']);

        // РАНО подключаем ассеты, только если на странице есть шорткод
        add_action('wp_enqueue_scripts', [self::class, 'maybe_enqueue_assets'], 5);
    }

    public static function maybe_enqueue_assets(): void
    {
        if (!is_singular()) {
            return; 
        }

        global $post;
        if (!$post || empty($post->post_content)) {
            return;
        }

        // Есть нужный шорткод в контенте → грузим ассеты активной темы
        if (has_shortcode($post->post_content, 'football_catalog')) {
            Assets::enqueue();
        }
    }

    public static function render($atts = []): string
    {
        $atts = shortcode_atts([
            'entry'  => 'football',
            'status' => null,
            'period' => null,
            'date'   => null,
        ], $atts);

        $controller = new CatalogController($atts);
        $html = $controller->render() ?? '';

        return '<section id="post-sgw-catalog" class="post-sgw-catalog">' . $html . '</section>';
    }
}
