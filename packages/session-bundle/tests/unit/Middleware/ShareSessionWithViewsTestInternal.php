<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use Snicco\SessionBundle\Keys;
use Snicco\Session\SessionErrors;
use Snicco\View\GlobalViewContext;
use Snicco\Session\ImmutableSession;
use Snicco\Session\ValueObjects\CsrfToken;
use Tests\HttpRouting\InternalMiddlewareTestCase;
use Snicco\SessionBundle\ImmutableSessionWrapper;
use Tests\Codeception\shared\helpers\SessionHelpers;
use Snicco\SessionBundle\Middleware\ShareSessionWithViews;

final class ShareSessionWithViewsTestInternal extends InternalMiddlewareTestCase
{
    
    use SessionHelpers;
    
    /** @test */
    public function nothing_is_shared_on_unsafe_requests()
    {
        $middleware = new ShareSessionWithViews($context = new GlobalViewContext());
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('POST'));
        $response->assertNextMiddlewareCalled();
        
        $this->assertEmpty($context->get());
    }
    
    /** @test */
    public function session_utils_are_shared_with_get_requests()
    {
        $session = $this->newSession();
        $session->withErrors(['foo' => 'bar']);
        
        $request = $this->frontendRequest();
        $request = $request->withAttribute(
            Keys::READ_SESSION,
            $session = ImmutableSession::fromSession($session)
        );
        
        $middleware = new ShareSessionWithViews($context = new GlobalViewContext());
        
        $response = $this->runMiddleware($middleware, $request);
        $response->assertNextMiddlewareCalled();
        
        $this->assertSame($session, $context->get()['session']);
        $this->assertInstanceOf(SessionErrors::class, $errors = $context->get()['errors']);
        $this->assertSame('bar', $errors->first('foo'));
        $this->assertInstanceOf(CsrfToken::class, $context->get()['csrf']);
    }
    
}