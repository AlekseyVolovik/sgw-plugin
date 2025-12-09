<?php
declare(strict_types=1);

namespace SGWPlugin\Classes;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use SGWPlugin\Theme\ThemeManager;

class Twig
{
    private static ?Twig $instance = null;
    private Environment $twig;

    public function __construct()
    {
        // Делаем loader явным, чтобы контролировать порядок приоритетов
        $loader = new FilesystemLoader();

        // ===== 1) Пути активной темы (если заданы в ThemeManager) =====
        // ThemeManager::twigViews() может вернуть:
        //  - список путей: [ '/.../themes/platina/views', '/.../themes/platina/templates' ]
        //  - или карту:     [ 'components'=>'...', 'pages'=>'...' ]
        $themeViews = ThemeManager::twigViews();
        $namespaces = ['components', 'blocks', 'parts', 'pages'];

        if (is_array($themeViews) && !empty($themeViews)) {
            $isAssoc = static function (array $arr): bool {
                return array_keys($arr) !== range(0, count($arr) - 1);
            };

            if ($isAssoc($themeViews)) {
                // ассоциативный вариант: ['components'=>..., ...]
                foreach ($namespaces as $ns) {
                    $dir = $themeViews[$ns] ?? null;
                    if (is_string($dir) && $dir !== '' && is_dir($dir)) {
                        $loader->addPath($dir, $ns); // namespaced include: @components/...
                    }
                }
                // также добавим корневые пути темы, если они присутствуют в карте
                foreach ($themeViews as $dir) {
                    if (is_string($dir) && is_dir($dir)) {
                        $loader->addPath($dir); // без namespace: pages/... и т.п.
                    }
                }
            } else {
                // список путей: добавляем каждый как основной путь,
                // а также пытаемся подключить подпапки с неймспейсами
                foreach ($themeViews as $base) {
                    if (!is_string($base) || $base === '' || !is_dir($base)) {
                        continue;
                    }
                    // как общий корень темы
                    $loader->addPath($base);

                    // и как неймспейсы, если есть подпапки
                    foreach ($namespaces as $ns) {
                        $sub = rtrim($base, '/\\') . '/' . $ns;
                        if (is_dir($sub)) {
                            $loader->addPath($sub, $ns);
                        }
                    }
                }
            }
        }

        // ===== 2) Фолбэк на ядро плагина (mc-front) =====
        // Сохраняем твою логику, но даём ей более низкий приоритет
        $corePaths = [
            'components' => SGWPLUGIN_PATH_FRONT . '/components',
            'blocks'     => SGWPLUGIN_PATH_FRONT . '/blocks',
            'parts'      => SGWPLUGIN_PATH_FRONT . '/parts',
            'pages'      => SGWPLUGIN_PATH_FRONT . '/pages',
        ];

        // общий корень mc-front (как у тебя в исходнике)
        if (defined('SGWPLUGIN_PATH_FRONT') && is_dir(SGWPLUGIN_PATH_FRONT)) {
            $loader->addPath(SGWPLUGIN_PATH_FRONT);
        }

        foreach ($corePaths as $ns => $dir) {
            if (is_dir($dir)) {
                $loader->addPath($dir, $ns);
            }
        }

        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => false,
        ]);

        $this->add_functions();
    }

    private function add_functions(): void
    {
        $this->twig->addFunction(new TwigFunction('home_url', fn () => get_home_url()));
    }

    public static function render(string $template, array $data): ?string
    {
        try {
            return self::instance()->twig->render($template, $data);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            // Покажем краткую ошибку в лог, но не ломаем фронт
            error_log('Twig Error: ' . $e->getMessage());
            return null;
        }
    }

    private static function instance(): Twig
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
