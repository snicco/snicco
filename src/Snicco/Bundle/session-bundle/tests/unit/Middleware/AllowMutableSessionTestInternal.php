<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use Snicco\SessionBundle\Keys;
use Snicco\Session\Contracts\SessionInterface;
use Tests\HttpRouting\InternalMiddlewareTestCase;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Tests\Codeception\shared\helpers\SessionHelpers;
use Snicco\SessionBundle\Middleware\AllowMutableSession;

final class AllowMutableSessionTestInternal extends InternalMiddlewareTestCase
{
    
    use SessionHelpers;
    
    /** @test */
    public function a_write_session_is_added_for_get_requests()
    {
        $middleware = new AllowMutableSession($this->getSessionManager());
        
        $request = $this->frontendRequest();
        
        $this->withNextMiddlewareResponse(function (Response $response, Request $request) {
            $this->assertInstanceOf(
                SessionInterface::class,
                $request->getAttribute(Keys::WRITE_SESSION)
            );
            
            return $response;
        });
        
        $this->runMiddleware($middleware, $request)->assertNextMiddlewareCalled();
    }
    
}