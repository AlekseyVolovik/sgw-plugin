<?php

declare(strict_types=1);

namespace SGWPlugin\Classes;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Twig
{
    private static ?Twig $instance = null;
    private ?Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader([
            SGWPLUGIN_PATH_FRONT
        ]);
        $loader->addPath(SGWPLUGIN_PATH_FRONT . '/components', 'components');
        $loader->addPath(SGWPLUGIN_PATH_FRONT . '/blocks', 'blocks');
        $loader->addPath(SGWPLUGIN_PATH_FRONT . '/parts', 'parts');
        $loader->addPath(SGWPLUGIN_PATH_FRONT . '/pages', 'pages');

        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true
        ]);

        $this->add_functions();
    }

    private function add_functions(): void
    {
        $this->twig->addFunction(new TwigFunction('home_url', fn() => get_home_url()));
    }

    public static function render(string $template, array $data): ?string
    {
        try {
            return self::instance()->twig->render($template, $data);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            var_dump($e->getMessage());
            error_log('Twig Error: ' . $e);
            return null;
        }
    }

    private static function instance(): ?Twig
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
}
