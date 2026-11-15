<?php
namespace SpotCore;

use SpotCore\Middleware\MiddlewareInterface;

/**
 * The HTTP kernel: Request -> Router -> middleware pipeline -> controller -> Response.
 */
final class App
{
    private Router $router;
    private array $globalMiddleware = [];
    private array $aliases = [];

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function setGlobalMiddleware(array $middleware): void { $this->globalMiddleware = $middleware; }
    public function setMiddlewareAliases(array $aliases): void { $this->aliases = $aliases; }

    public function handle(Request $request): Response
    {
        // Global middleware (logging, security headers) wraps everything, including 404/405 pages.
        $inner = function (Request $req): Response {
            try {
                $route = $this->router->match($req);
                $req->setParams($route['params']);
                $controller = fn(Request $r): Response => $this->callHandler($route['handler'], $r);
                return $this->pipeline($route['middleware'], $controller)($req);
            } catch (HttpException $e) {
                $response = $this->errorResponse($e->status(), $e->getMessage());
                foreach ($e->headers() as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }
                return $response;
            } catch (\Throwable $e) {
                // Details go to the private log only, never to the visitor.
                Logger::error(get_class($e) . ': ' . $e->getMessage(), ['file' => basename($e->getFile()), 'line' => $e->getLine()]);
                $message = Config::get('app.debug')
                    ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
                    : 'Something went wrong on our side. Please try again.';
                return $this->errorResponse(500, $message);
            }
        };

        return $this->pipeline($this->globalMiddleware, $inner)($request);
    }

    /** Wraps $core in middleware layers; the first listed runs first (onion model). */
    private function pipeline(array $stack, callable $core): callable
    {
        return array_reduce(
            array_reverse($stack),
            fn(callable $next, string $name) =>
                fn(Request $req): Response => $this->resolveMiddleware($name)->handle($req, $next),
            $core,
        );
    }

    private function callHandler(callable|array $handler, Request $request): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $result = (new $class())->$method($request);
        } else {
            $result = $handler($request);
        }

        if ($result instanceof Response) {
            return $result;
        }
        return is_array($result) ? Response::json($result) : Response::html((string) $result);
    }

    private function resolveMiddleware(string $name): MiddlewareInterface
    {
        $class = $this->aliases[$name] ?? $name;
        if (!class_exists($class)) {
            throw new \RuntimeException("Middleware [{$name}] not found.");
        }
        $instance = new $class();
        if (!$instance instanceof MiddlewareInterface) {
            throw new \RuntimeException("{$class} must implement MiddlewareInterface.");
        }
        return $instance;
    }

    private function errorResponse(int $status, string $message): Response
    {
        try {
            return Response::html(View::render('errors/error', ['title' => 'Error ' . $status, 'status' => $status, 'message' => $message]), $status);
        } catch (\Throwable $e) {
            return Response::html('<h1>' . $status . '</h1><p>' . htmlspecialchars($message) . '</p>', $status);
        }
    }
}
