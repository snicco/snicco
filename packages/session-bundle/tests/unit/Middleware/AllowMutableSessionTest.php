<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use Snicco\SessionBundle\Keys;
use Snicco\Core\Http\Psr7\Request;
use Tests\Core\MiddlewareTestCase;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Session\Contracts\SessionInterface;
use Tests\Codeception\shared\helpers\SessionHelpers;
use Snicco\SessionBundle\Middleware\AllowMutableSession;

final class AllowMutableSessionTest extends MiddlewareTestCase
{
    
    use SessionHelpers;
    
    /** @test */
    public function a_write_session_is_added_for_get_requests()
    {
        $middleware = new AllowMutableSession($this->getSessionManager());
        
        $request = $this->frontendRequest();
        
        $this->setNextMiddlewareResponse(function (Response $response, Request $request) {
            $this->assertInstanceOf(
                SessionInterface::class,
                $request->getAttribute(Keys::WRITE_SESSION)
            );
            
            return $response;
        });
        
        $this->runMiddleware($middleware, $request)->assertNextMiddlewareCalled();
    }
    
}