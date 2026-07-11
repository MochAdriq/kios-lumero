<?php
class Router
{
    private array $routes = [];

    public function get(string $path, $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, $handler): void
    {
        $this->routes[] = compact('method', 'path', 'handler');
    }

    private function currentUri(): string
    {
        if (function_exists('current_route_path')) {
            return current_route_path();
        }

        if (!empty($_GET['route'])) {
            $route = '/' . trim((string)$_GET['route'], '/');
            return $route === '/' ? '/' : $route;
        }

        return '/';
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->currentUri();
        error_log("Router dispatching: method=$method, uri=$uri, REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '') . ", route=" . ($_GET['route'] ?? ''));

        foreach ($this->routes as $r) {
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([0-9A-Za-z_\-]+)', $r['path']);
            $pattern = '#^' . $pattern . '$#';
            if ($method === $r['method'] && preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $this->call($r['handler'], $matches);
                return;
            }
        }

        http_response_code(404);
        include __DIR__ . '/../views/errors/404.php';
    }

    private function call($handler, array $params = []): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            (new $class())->{$method}(...$params);
            return;
        }
        $handler(...$params);
    }
}
