<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use Snicco\SessionBundle\Keys;
use Tests\Core\InternalMiddlewareTestCase;
use Snicco\Session\ImmutableSession;
use Snicco\Session\ValueObjects\CsrfToken;
use Tests\Codeception\shared\helpers\SessionHelpers;
use Snicco\SessionBundle\Middleware\VerifyCsrfToken;
use Snicco\SessionBundle\Exceptions\InvalidCsrfTokenException;

class VerifyCsrfTokenTestInternal extends InternalMiddlewareTestCase
{
    
    use SessionHelpers;
    
    /** @test */
    public function save_request_methods_are_not_affected()
    {
        $middleware = new VerifyCsrfToken();
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->runMiddleware($middleware, $request)->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function missing_csrf_tokens_will_fail()
    {
        $middleware = new VerifyCsrfToken();
        $request = $this->frontendRequest('POST', '/foo');
        $request = $request->withAttribute(
            Keys::READ_SESSION,
            ImmutableSession::fromSession($this->newSession())
        );
        
        $this->expectException(InvalidCsrfTokenException::class);
        
        $this->runMiddleware($middleware, $request);
    }
    
    /** @test */
    public function invalid_csrf_tokens_will_fail()
    {
        $middleware = new VerifyCsrfToken();
        $request = $this->frontendRequest('POST', '/foo')->withParsedBody([
            CsrfToken::INPUT_KEY => 'bogus',
        ]);
        $request = $request->withAttribute(
            Keys::READ_SESSION,
            ImmutableSession::fromSession($this->newSession())
        );
        
        $this->expectException(InvalidCsrfTokenException::class);
        
        $this->runMiddleware($middleware, $request);
    }
    
    /** @test */
    public function matching_csrf_tokens_will_work()
    {
        $session = ImmutableSession::fromSession($this->newSession());
        
        $middleware = new VerifyCsrfToken();
        $request = $this->frontendRequest('POST', '/foo')->withParsedBody([
            CsrfToken::INPUT_KEY => $session->csrfToken()->asString(),
        ]);
        $request = $request->withAttribute(
            Keys::READ_SESSION,
            $session
        );
        
        $this->runMiddleware($middleware, $request)->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function csrf_tokens_can_be_accepted_in_the_header()
    {
        $session = ImmutableSession::fromSession($this->newSession());
        
        $middleware = new VerifyCsrfToken();
        $request = $this->frontendRequest('POST', '/foo')->withAddedHeader(
            'X-CSRF-TOKEN',
            $session->csrfToken()->asString()
        );
        $request = $request->withAttribute(
            Keys::READ_SESSION,
            $session
        );
        
        $this->runMiddleware($middleware, $request)->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function urls_can_be_excluded_from_the_csrf_protection()
    {
        $middleware = new VerifyCsrfToken(['foo/*']);
        $request = $this->frontendRequest('POST', '/foo/bar')->withParsedBody([]);
        $request = $request->withAttribute(
            Keys::READ_SESSION,
            ImmutableSession::fromSession($this->newSession())
        );
        
        $this->runMiddleware($middleware, $request)->assertNextMiddlewareCalled();
    }
    
}