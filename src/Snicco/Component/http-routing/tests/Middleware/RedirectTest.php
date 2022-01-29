<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use Snicco\Component\HttpRouting\Middleware\Redirect;
use Snicco\Component\HttpRouting\Tests\InternalMiddlewareTestCase;

class RedirectTest extends InternalMiddlewareTestCase
{
    
    protected function tearDown() :void
    {
        parent::tearDown();
        
        if (is_file(__DIR__.DIRECTORY_SEPARATOR.'/redirects.json')) {
            unlink(__DIR__.DIRECTORY_SEPARATOR.'/redirects.json');
        }
    }
    
    /** @test */
    public function testRedirectForConfiguredUrls()
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
        ]);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        
        $response->assertNextMiddlewareNotCalled();
        $response->assertRedirect('/bar')->assertStatus(301);
    }
    
    /** @test */
    public function other_requests_are_not_redirected()
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
        ]);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/bogus'));
        
        $response->assertNextMiddlewareCalled();
        $response->assertOk();
    }
    
    /** @test */
    public function test_redirects_can_have_a_custom_status_code()
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
            302 => [
                '/baz' => '/biz',
                '/a/' => '/b/',
            ],
            307 => [
                '/boo/' => '/bam/',
            ],
        ]);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $response->assertRedirect('/bar', 301);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/baz'));
        $response->assertRedirect('/biz', 302);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/boo'));
        $response->assertRedirect('/bam', 307);
    }
    
    /** @test */
    public function the_redirect_map_can_create_a_cache_file()
    {
        $file = __DIR__.DIRECTORY_SEPARATOR.'redirects.json';
        $this->assertFalse(is_file($file));
        
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
            302 => [
                '/baz' => '/biz',
                '/a/' => '/b/',
            ],
            307 => [
                '/boo/' => '/bam/',
            ],
        ], $file);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $response->assertRedirect('/bar', 301);
        
        $this->assertTrue(is_file($file), 'Redirect map not cached.');
    }
    
    /** @test */
    public function redirects_are_not_loaded_from_the_cache_file_if_the_cache_argument_is_omitted()
    {
        $file = __DIR__.DIRECTORY_SEPARATOR.'redirects.json';
        
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
        ], $file);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $response->assertRedirect('/bar', 301);
        
        $this->assertTrue(is_file($file), 'Redirect map not cached.');
        
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/other',
            ],
        ]);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $response->assertRedirect('/other', 301);
    }
    
    /** @test */
    public function testRedirectsMatchTheFullPathIncludingQueryParams()
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo?page=60' => '/bar',
            ],
        ]);
        
        $response =
            $this->runMiddleware($middleware, $this->frontendRequest('/foo?page=60'));
        
        $response->assertRedirect('/bar')->assertStatus(301);
    }
    
    /** @test */
    public function two_urls_can_be_redirected_to_the_same_location()
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
                '/baz' => '/bar',
            ],
        ]);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $response->assertRedirect('/bar', 301);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/baz'));
        $response->assertRedirect('/bar', 301);
    }
    
    private function getMiddleware(array $redirects = [], string $cache_file = null) :Redirect
    {
        return new Redirect($redirects, $cache_file);
    }
    
}