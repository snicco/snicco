<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Snicco\Contracts\Redirector;
use Snicco\Http\StatelessRedirector;
use Tests\Core\fixtures\TestDoubles\HeaderStack;

class RedirectRoutesTest extends RoutingTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bindRedirector();
    }
    
    /** @test */
    public function a_redirect_route_can_be_created()
    {
        $this->createRoutes(function () {
            $this->router->redirect('/foo', '/bar');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('', $request);
        
        HeaderStack::assertHasStatusCode(302);
        HeaderStack::assertContains('Location', '/bar');
    }
    
    /** @test */
    public function a_permanent_redirect_can_be_created()
    {
        $this->createRoutes(function () {
            $this->router->permanentRedirect('/foo', '/bar');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('', $request);
        
        HeaderStack::assertHasStatusCode(301);
        HeaderStack::assertContains('Location', '/bar');
    }
    
    /** @test */
    public function a_temporary_redirect_can_be_created()
    {
        $this->createRoutes(function () {
            $this->router->temporaryRedirect('/foo', '/bar');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('', $request);
        
        HeaderStack::assertHasStatusCode(307);
        HeaderStack::assertContains('Location', '/bar');
    }
    
    /** @test */
    public function a_redirect_to_an_external_url_can_be_created()
    {
        $this->createRoutes(function () {
            $this->router->redirectAway('/foo', 'https://foobar.com/', 303);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('', $request);
        
        HeaderStack::assertHasStatusCode(303);
        HeaderStack::assertContains('Location', 'https://foobar.com/');
    }
    
    /** @test */
    public function a_redirect_to_a_route_can_be_created()
    {
        $this->createRoutes(function () {
            $this->router->get('/base/{param}', function () {
                //
            })->name('base');
            
            $this->router->redirectToRoute('/foo', 'base', ['param' => 'baz'], 304);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('', $request);
        
        HeaderStack::assertHasStatusCode(304);
        HeaderStack::assertContains('Location', 'base/baz');
    }
    
    /** @test */
    public function regex_based_redirects_works()
    
    {
        $this->createRoutes(function () {
            $this->router->redirect('base/{slug}', 'base/new')
                         ->andEither('slug', ['foo', 'bar']);
            
            $this->router->get('base/biz', function () {
                return 'biz';
            });
            
            $this->router->get('base/{path}', function (string $path) {
                return $path;
            })->andEither('path', ['bam', 'boom']);
        });
        
        $request = $this->frontendRequest('GET', 'base/foo');
        $this->assertResponse('', $request);
        HeaderStack::assertHasStatusCode(302);
        HeaderStack::assertContains('Location', '/base/new');
        HeaderStack::reset();
        
        $request = $this->frontendRequest('GET', 'base/bar');
        $this->assertResponse('', $request);
        HeaderStack::assertHasStatusCode(302);
        HeaderStack::assertContains('Location', '/base/new');
        HeaderStack::reset();
        
        $request = $this->frontendRequest('GET', 'base/baz');
        $this->assertResponse('', $request);
        HeaderStack::assertNoStatusCodeSent();
        HeaderStack::reset();
        
        $request = $this->frontendRequest('GET', 'base/biz');
        $this->assertResponse('biz', $request);
        HeaderStack::assertHasStatusCode(200);
        HeaderStack::reset();
        
        $request = $this->frontendRequest('GET', 'base/boom');
        $this->assertResponse('boom', $request);
        HeaderStack::assertHasStatusCode(200);
        HeaderStack::reset();
        
        $request = $this->frontendRequest('GET', 'base/bam');
        $this->assertResponse('bam', $request);
        HeaderStack::assertHasStatusCode(200);
        HeaderStack::reset();
    }
    
    private function bindRedirector()
    {
        $this->container->instance(
            Redirector::class,
            new StatelessRedirector($this->newUrlGenerator(), $this->psrResponseFactory())
        );
    }
    
}