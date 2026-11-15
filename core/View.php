<?php
namespace SpotCore;

/**
 * Plain-PHP templates. render('orders.show', [...]) loads
 * app/Views/orders/show.php and wraps it in the layout,
 * which prints the page with <?= $content ?>.
 */
final class View
{
    private static string $path = '';

    public static function setPath(string $path): void
    {
        self::$path = rtrim($path, '/');
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = self::renderFile($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::renderFile($layout, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $view, array $data = []): string
    {
        return self::renderFile($view, $data);
    }

    private static function renderFile(string $view, array $data): string
    {
        $file = self::$path . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View [{$view}] not found.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
