<?php declare(strict_types=1);

namespace SGWPlugin\Classes;

use function add_filter;
use function body_class;
use function class_exists;
use function get_bloginfo;
use function get_header;
use function home_url;
use function ob_get_clean;
use function ob_start;

class ThemeHeader
{
    public static function render(array $args = []): bool
    {
        if (defined('SGW_LATTER_HEADER_RENDERED')) {
            return true;
        }

        $title      = (string)($args['title'] ?? '') ?: get_bloginfo('name');
        $canonical  = (string)($args['canonical'] ?? '') ?: home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? ''));
        $desc       = (string)($args['description'] ?? '');

        if (
            class_exists('\Latter\Front\Fields') &&
            class_exists('\Latter\Front\Filters') &&
            class_exists('\Latter\Parts\header\Header') &&
            class_exists('\Timber\Timber')
        ) {
            define('SGW_LATTER_HEADER_RENDERED', true);

            $custom_header_data = [
                'title'        => $title,
                'canonicalUrl' => $canonical,
                'description'  => $desc,
            ];

            add_filter('document_title_parts', function($title_parts) use ($custom_header_data) {
                return ['title' => $custom_header_data['title']];
            });

            $site_setup = \Latter\Front\Fields::get_site_setup();
            $headerObj  = new \Latter\Parts\header\Header();

            // Head (Twig)
            echo \Timber\Timber::compile('app/Parts/head/view.twig', [
                'canonical' => $custom_header_data['canonicalUrl'] ?? '',
            ]);

            ob_start(); body_class(); $body_attr = ob_get_clean();
            echo '<body ' . $body_attr . '>';

            echo \Latter\Front\Filters::nofollow_links($headerObj->render());

            if (isset($site_setup['metrics']['noscript'])) {
                echo $site_setup['metrics']['noscript'];
            }

            return true;
        }

        get_header();
        return false;
    }
}
