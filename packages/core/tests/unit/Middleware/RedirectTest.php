<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Snicco\Middleware\Redirect;
use Tests\Core\MiddlewareTestCase;

class RedirectTest extends MiddlewareTestCase
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
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
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
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/bogus'));
        
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
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        $response->assertRedirect('/bar', 301);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/baz'));
        $response->assertRedirect('/biz', 302);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/boo'));
        $response->assertRedirect('/bam', 307);
    }
    
    /** @test */
    public function the_redirect_map_can_create_a_cache_file()
    {
        $file = __DIR__.DIRECTORY_SEPARATOR.'redirects.json';
        $this->assertFalse(file_exists($file));
        
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
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        $response->assertRedirect('/bar', 301);
        
        $this->assertTrue(file_exists($file), 'Redirect map not cached.');
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
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        $response->assertRedirect('/bar', 301);
        
        $this->assertTrue(file_exists($file), 'Redirect map not cached.');
        
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/other',
            ],
        ]);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
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
            $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo?page=60'));
        
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
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        $response->assertRedirect('/bar', 301);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/baz'));
        $response->assertRedirect('/bar', 301);
    }
    
    private function getMiddleware(array $redirects = [], string $cache_file = null) :Redirect
    {
        return new Redirect($redirects, $cache_file);
    }
    
}