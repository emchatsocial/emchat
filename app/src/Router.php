<?php
declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->map('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->map('POST', $pattern, $handler);
    }

    public function map(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $allowed = [];
        foreach ($this->routes as $route) {
            $regex = $this->toRegex($route['pattern']);
            if (preg_match($regex, $path, $matches)) {
                if ($route['method'] !== $method) {
                    $allowed[] = $route['method'];
                    continue;
                }
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                ($route['handler'])($params);
                return;
            }
        }

        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            abort(405, 'Method not allowed.');
        }
        abort(404);
    }

    private function toRegex(string $pattern): string
    {
        $regex = preg_replace_callback('~\{([a-zA-Z_]+)(:[^}]+)?\}~', static function ($m) {
            $name = $m[1];
            $sub = isset($m[2]) ? substr($m[2], 1) : '[^/]+';
            return '(?P<' . $name . '>' . $sub . ')';
        }, $pattern);
        return '~^' . $regex . '$~';
    }
}
