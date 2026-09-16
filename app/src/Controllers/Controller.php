<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Request;
use App\View;

abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $this->view->render($view, $data, $layout);
    }

    protected function verifyCsrf(): void
    {
        if (!csrf_verify(Request::raw('_csrf'))) {
            if (Request::wantsJson()) {
                json_response(['ok' => false, 'error' => 'Your session expired. Refresh and try again.'], 419);
            }
            abort(419, 'Your session expired. Please go back, refresh, and try again.');
        }
    }

    protected function meta(array $overrides = []): array
    {
        return array_merge([
            'title'       => config('app_name'),
            'description' => config('app_tagline'),
            'canonical'   => url(Request::path() === '/' ? '/' : Request::path()),
            'robots'      => 'index,follow',
            'og_image'    => url('/assets/img/og-default.png?v=2'),
            'type'        => 'website',
        ], $overrides);
    }
}
