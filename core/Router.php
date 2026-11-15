<?php
namespace SpotCore;

/**
 * Maps "METHOD /path" to a handler.
 *
 *   $router->get('/places/{id}', [PlaceController::class, 'show']);
 *   $router->group(['auth'], fn($r) => ..., '/admin');
 *
 * Route parameters only match positive integers ({id} -> [1-9][0-9]*), so a
 * static route such as /admin/places/create can never be mistaken for an ID.
 */
final class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $path = Request::normalizePath($this->groupPrefix . '/' . trim($path, '/'));
        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $path,
            'regex'      => $this->compile($path),
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    /** Every route registered inside $callback shares the middleware and URL prefix. */
    public function group(array $middleware, callable $callback, string $prefix = ''): void
    {
        $prevMiddleware = $this->groupMiddleware;
        $prevPrefix = $this->groupPrefix;

        $this->groupMiddleware = array_merge($prevMiddleware, $middleware);
        $this->groupPrefix = $prevPrefix . $prefix;

        $callback($this);

        $this->groupMiddleware = $prevMiddleware;
        $this->groupPrefix = $prevPrefix;
    }

    /**
     * @return array{handler: callable|array, params: array, middleware: array}
     * @throws HttpException 404 when no path matches, 405 when only the method is wrong
     */
    public function match(Request $request): array
    {
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path(), $m)) {
                continue;
            }
            if ($route['method'] !== $request->method()) {
                $allowed[] = $route['method'];
                continue;
            }
            $params = [];
            foreach ($m as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = (int) $value;
                }
            }
            return ['handler' => $route['handler'], 'params' => $params, 'middleware' => $route['middleware']];
        }

        if ($allowed) {
            throw new HttpException(405, 'This page does not accept that kind of request.', ['Allow' => implode(', ', array_unique($allowed))]);
        }
        throw new HttpException(404, 'The page you are looking for does not exist.');
    }

    /** '/places/{id}' -> '#^/places/(?P<id>[1-9][0-9]*)$#' */
    private function compile(string $path): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[1-9][0-9]{0,9})', $path);
        return '#^' . $regex . '$#';
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
