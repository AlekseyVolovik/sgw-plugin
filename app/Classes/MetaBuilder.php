<?php declare(strict_types=1);

namespace SGWPlugin\Classes;

class MetaBuilder
{
    private static ?string $title = null;
    private static ?string $description = null;

    public static function setTitle(string $title): void
    {
        self::$title = $title;
    }

    public static function setDescription(string $description): void
    {
        self::$description = $description;
    }

    public static function output(): void
    {
        if (self::$title) {
            echo '<title>' . esc_html(self::$title) . '</title>' . PHP_EOL;
        }

        if (self::$description) {
            echo '<meta name="description" content="' . esc_attr(self::$description) . '">' . PHP_EOL;
        }
    }

    public static function isDynamicPage(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        return preg_match('#^/(football(/[^/]+(/[^/]+)?)?|live|today|tomorrow|yesterday|upcoming|finished)(/.*)?$#', $uri) === 1;
    }

    // ===== 🎯 НОВОЕ: поддержка шаблонов =====

    public static function loadTemplates(): array
    {
        static $templates = null;

        if ($templates === null) {
            $path = SGWPLUGIN_PATH . 'app/Meta/templates.php';
            if (file_exists($path)) {
                $templates = require $path;
            } else {
                $templates = [];
            }
        }

        return $templates;
    }

    public static function getTemplate(string $key, string $type = 'title'): ?string
    {
        $templates = self::loadTemplates(); // Используем кешированную загрузку

        if (!isset($templates[$key])) {
            return null;
        }

        $templateEntry = $templates[$key];

        if (is_array($templateEntry)) {
            return $templateEntry[$type] ?? null;
        }

        // Старый формат, где только title в виде строки
        return $type === 'title' ? $templateEntry : null;
    }

    public static function buildMeta(string $template, array $variables = []): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }
}
