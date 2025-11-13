<?php declare(strict_types=1);

namespace SGWPlugin\Classes;

use function class_exists;
use function get_footer;
use function wp_footer;

class ThemeFooter
{
    public static function render(array $args = []): bool
    {
        if (defined('SGW_LATTER_FOOTER_RENDERED')) {
            return true;
        }

        if (
            class_exists('\Latter\Front\Fields') &&
            class_exists('\Latter\Front\Filters') &&
            class_exists('\Latter\Parts\footer\Footer') &&
            class_exists('\Latter\Parts\topButton\TopButton') &&
            class_exists('\Latter\Parts\searchPopup\SearchPopup') &&
            class_exists('\Latter\Front\Buttons') &&
            class_exists('\Latter\Parts\downloadCard\DownloadCard')
        ) {
            define('SGW_LATTER_FOOTER_RENDERED', true);

            $footer_data = [
                'custom-mobile-button-link' => [
                    'url' => (string)($args['mobile_button_url'] ?? '')
                ],
            ];

            $site_setup = \Latter\Front\Fields::get_site_setup();

            $banners_buttons = new \Latter\Front\Buttons(-1);
            if ($banners_buttons->get_active_button()) {
                $button_setup = $banners_buttons->get_active_button();

                if (!empty($button_setup['setup']['link']['url']) && !empty($footer_data['custom-mobile-button-link']['url'])) {
                    $button_setup['setup']['link']['url'] = $footer_data['custom-mobile-button-link']['url'];
                }

                $button = new \Latter\Parts\downloadCard\DownloadCard($button_setup);
                echo $button->render();
            }

            $footer = new \Latter\Parts\footer\Footer();
            echo \Latter\Front\Filters::nofollow_links($footer->render());

            $top_button = new \Latter\Parts\topButton\TopButton();
            echo $top_button->render();

            $search_popup = new \Latter\Parts\searchPopup\SearchPopup();
            echo $search_popup->render();

            wp_footer();

            if (isset($site_setup['metrics']['main'])) {
                echo $site_setup['metrics']['main'];
            }

            return true;
        }

        get_footer();
        return false;
    }
}
