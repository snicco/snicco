<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use Snicco\SessionBundle\Keys;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\MessageBag;
use Tests\HttpRouting\InternalMiddlewareTestCase;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\Session\Tests\fixtures\SessionHelpers;
use Snicco\SessionBundle\Middleware\AddResponseAttributesToSession;

final class AddResponseAttributesToSessionTestInternal extends InternalMiddlewareTestCase
{
    
    use SessionHelpers;
    
    /**
     * @var Request
     */
    private $request;
    
    /**
     * @var Session
     */
    private $session;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->request = $this->frontendRequest();
        $this->session = $this->newSession();
        $this->request = $this->request->withAttribute(Keys::WRITE_SESSION, $this->session);
    }
    
    /** @test */
    public function errors_are_added_to_the_session()
    {
        $middleware = new AddResponseAttributesToSession();
        
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response->withErrors([
                'foo' => [
                    'bar',
                    'baz',
                ],
            ], 'name1');
        });
        
        $response = $this->runMiddleware($middleware, $this->request);
        
        $response->assertNextMiddlewareCalled();
        
        $errors = $this->session->errors();
        
        $this->assertInstanceOf(MessageBag::class, $bag = $errors->getBag('name1'));
        $this->assertSame(['bar', 'baz'], $bag->get('foo'));
    }
    
    /** @test */
    public function flash_messages_are_added()
    {
        $middleware = new AddResponseAttributesToSession();
        
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response->withFlashMessages('foo', 'bar');
        });
        
        $response = $this->runMiddleware($middleware, $this->request);
        
        $response->assertNextMiddlewareCalled();
        
        $this->assertSame('bar', $this->session->get('foo'));
    }
    
    /** @test */
    public function old_input_is_added()
    {
        $middleware = new AddResponseAttributesToSession();
        
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response->withOldInput('foo', 'bar');
        });
        
        $response = $this->runMiddleware($middleware, $this->request);
        
        $response->assertNextMiddlewareCalled();
        
        $this->assertSame('bar', $this->session->oldInput('foo'));
    }
    
}