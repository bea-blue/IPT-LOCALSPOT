<?php

use SpotCore\Config;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Testing\TestCase;

final class HttpTest extends TestCase
{
    public function testPathNormalization(): void
    {
        $this->assertSame('/', Request::normalizePath(''));
        $this->assertSame('/admin/places', Request::normalizePath('//admin//places/'));
    }

    public function testCaptureStripsConfiguredBasePath(): void
    {
        $_SERVER['REQUEST_URI'] = '/localspot/public/places/3?x=1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = Request::capture('/localspot/public');
        $this->assertSame('/places/3', $request->path());
    }

    public function testInputRejectsArrays(): void
    {
        $request = new Request('POST', '/x', [], ['name' => ['a', 'b'], 'city' => '  Angeles  ']);
        $this->assertSame('', $request->input('name', ''));
        $this->assertSame('Angeles', $request->input('city'));
    }

    public function testRedirectsStayInsideTheApp(): void
    {
        Config::set('app.base_path', '');
        $this->assertSame('/admin', Response::redirect('/admin')->header('Location'));
        $this->assertSame('/', Response::redirect('https://evil.test')->header('Location'));
        $this->assertSame('/', Response::redirect('//evil.test')->header('Location'));
        $this->assertSame(303, Response::redirect('/admin')->status());
    }

    public function testUrlHelperUsesBasePath(): void
    {
        Config::set('app.base_path', '/localspot/public');
        $this->assertSame('/localspot/public/places/1', url('/places/1'));
        Config::set('app.base_path', '');
    }

    public function testEscapeHelperMakesHtmlInert(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
    }
}
