<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $method, string $uriPath): void
    {
        $method = strtoupper($method);
        $path = $this->normalize($uriPath);

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
        }

        http_response_code(404);
        echo 'Sayfa bulunamadı: ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
    }

    private function normalize(string $path): string
    {
        $trimmed = '/' . trim($path, '/');
        return $trimmed === '//' ? '/' : $trimmed;
    }
}