<?php

use SpotCore\HttpException;
use SpotCore\Request;
use SpotCore\Router;
use SpotCore\Testing\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', ['Home', 'index']);
        $this->router->get('/places/{id}', ['Place', 'show']);
        $this->router->group(['auth'], function (Router $r) {
            $r->get('/places/create', ['Admin', 'create']);
            $r->get('/places/{id}/edit', ['Admin', 'edit']);
            $r->post('/places/{id}/delete', ['Admin', 'destroy'], ['csrf']);
        }, '/admin');
    }

    public function testMatchesNumericParameterAsInteger(): void
    {
        $m = $this->router->match(new Request('GET', '/places/42'));
        $this->assertSame(['id' => 42], $m['params']);
    }

    public function testCreateIsNeverTreatedAsAnId(): void
    {
        $m = $this->router->match(new Request('GET', '/admin/places/create'));
        $this->assertSame(['Admin', 'create'], $m['handler']);
    }

    public function testNonPositiveOrTextIdsGive404(): void
    {
        foreach (['/places/0', '/places/-3', '/places/abc', '/places/1.5', '/places/007'] as $path) {
            $e = $this->assertThrows(HttpException::class, fn() => $this->router->match(new Request('GET', $path)));
            $this->assertSame(404, $e->status(), $path);
        }
    }

    public function testGroupAddsPrefixAndMiddleware(): void
    {
        $m = $this->router->match(new Request('POST', '/admin/places/7/delete'));
        $this->assertSame(['auth', 'csrf'], $m['middleware']);
    }

    public function testWrongMethodGives405WithAllowHeader(): void
    {
        $e = $this->assertThrows(HttpException::class, fn() => $this->router->match(new Request('GET', '/admin/places/7/delete')));
        $this->assertSame(405, $e->status());
        $this->assertSame(['Allow' => 'POST'], $e->headers());
    }

    public function testUnknownPathGives404(): void
    {
        $e = $this->assertThrows(HttpException::class, fn() => $this->router->match(new Request('GET', '/nope')));
        $this->assertSame(404, $e->status());
    }
}
