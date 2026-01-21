<?php

declare(strict_types=1);

namespace SmartAdd\Http;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function dispatch(Request $request, array $config = []): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $matches = [];
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($route['paramNames'] as $name) {
                if (array_key_exists($name, $matches)) {
                    $params[$name] = $matches[$name];
                }
            }

            return ($route['handler'])($request, $params, $config);
        }

        return Response::json(['error' => 'not_found'], 404);
    }

    private function addRoute(string $method, string $pattern, callable $handler): void
    {
        [$regex, $paramNames] = $this->compilePattern($pattern);
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
        ];
    }

    private function compilePattern(string $pattern): array
    {
        $paramNames = [];

        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(:([^\}]+))?\}/', static function (array $m) use (&$paramNames): string {
            $name = $m[1];
            $paramNames[] = $name;
            $sub = isset($m[3]) && $m[3] !== '' ? $m[3] : '[^/]+';
            return '(?P<' . $name . '>' . $sub . ')';
        }, $pattern);

        if (!is_string($regex)) {
            $regex = $pattern;
        }

        return ['#^' . $regex . '$#', $paramNames];
    }
}
