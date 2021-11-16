<?php

declare(strict_types=1);

namespace Snicco\Session;

use Snicco\Http\Psr7\Request;
use Snicco\Routing\UrlGenerator;
use Snicco\Contracts\Redirector;
use Snicco\Http\Responses\RedirectResponse;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

class StatefulRedirector extends Redirector
{
    
    private Session $session;
    
    public function __construct(Session $session, UrlGenerator $url_generator, Psr17ResponseFactory $response_factory)
    {
        parent::__construct($url_generator, $response_factory);
        $this->session = $session;
    }
    
    public function intended(Request $request, string $fallback = '', int $status = 302) :RedirectResponse
    {
        $path = $this->session->getIntendedUrl();
        
        if ($path) {
            return $this->createRedirectResponse($path, $status);
        }
        
        return parent::intended($request, $fallback, $status);
    }
    
    public function createRedirectResponse(string $path, int $status_code = 302) :RedirectResponse
    {
        $this->validateStatusCode($status_code);
        
        $psr_response = $this->response_factory->createResponse($status_code);
        
        return (new RedirectResponse($psr_response))
            ->to($path)
            ->withSession($this->session);
    }
    
    public function previous(int $status = 302, string $fallback = '') :RedirectResponse
    {
        $path = $this->session->getPreviousUrl($fallback);
        
        if ($path !== '') {
            return $this->createRedirectResponse($path, $status);
        }
        
        return $this->createRedirectResponse($this->generator->back('/'));
    }
    
    /**
     * Create a redirect response to the given path and store the intended url in the session.
     */
    public function guest(string $path, $status = 302, array $query = [], bool $secure = true, bool $absolute = true)
    {
        $request = $this->generator->getRequest();
        
        $intended = $request->getMethod() === 'GET' && ! $request->isAjax()
            ? $request->fullPath()
            : $this->session->getPreviousUrl('/');
        
        $this->session->setIntendedUrl($intended);
        
        return $this->to($path, $status, $query, $secure, $absolute);
    }
    
}