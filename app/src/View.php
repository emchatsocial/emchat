<?php
declare(strict_types=1);

namespace App;

final class View
{
    private string $viewPath;

    public function __construct()
    {
        $this->viewPath = APP_PATH . '/views';
    }

    /**
     * Render a view inside a layout.
     *
     * @param array<string,mixed> $data
     */
    public function render(string $view, array $data = [], string $layout = 'app'): void
    {
        echo $this->capture($view, $data, $layout);
    }

    public function capture(string $view, array $data = [], ?string $layout = 'app'): string
    {
        $content = $this->renderPartial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return $this->renderPartial('layouts/' . $layout, $data + [
            'content' => $content,
            'meta'    => $data['meta'] ?? [],
        ]);
    }

    /** Render a bare view/partial with no layout. */
    public function renderPartial(string $view, array $data = []): string
    {
        $file = $this->viewPath . '/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        $view = $this;
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    public function partial(string $view, array $data = []): string
    {
        return $this->renderPartial('partials/' . $view, $data);
    }
}
