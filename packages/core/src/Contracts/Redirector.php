<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use LogicException;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\UrlGenerator;
use Snicco\Core\Http\Responses\RedirectResponse;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

/**
 * @todo dedicated tests. This class has only implicit tests
 */
abstract class Redirector
{
    
    protected UrlGenerator $generator;
    
    protected Psr17ResponseFactory $response_factory;
    
    public function __construct(UrlGenerator $url_generator, Psr17ResponseFactory $response_factory)
    {
        $this->generator = $url_generator;
        $this->response_factory = $response_factory;
    }
    
    public function home($status = 302, bool $secure = true, bool $absolute = false) :RedirectResponse
    {
        return $this->to($this->generator->toRoute('home', [], $secure, $absolute), $status);
    }
    
    public function to(string $path, int $status = 302, array $query = [], bool $secure = true, bool $absolute = false) :RedirectResponse
    {
        return $this->createRedirectResponse(
            $this->generator->to($path, $query, $secure, $absolute),
            $status
        );
    }
    
    abstract public function createRedirectResponse(string $path, int $status_code = 302) :RedirectResponse;
    
    public function absoluteRedirect(string $path, int $status = 302, array $query = [], bool $secure = true) :RedirectResponse
    {
        return $this->to($path, $status, $query, $secure, true);
    }
    
    public function toRoute(string $name, int $status = 302, array $arguments = [], bool $secure = true, bool $absolute = false) :RedirectResponse
    {
        return $this->createRedirectResponse(
            $this->generator->toRoute($name, $arguments, $secure, $absolute),
            $status
        );
    }
    
    public function secure(string $path, int $status = 302, array $query = []) :RedirectResponse
    {
        return $this->to($path, $status, $query);
    }
    
    public function toLogin(string $redirect_on_login = '', bool $reauth = false, int $status_code = 302) :RedirectResponse
    {
        return $this->createRedirectResponse(
            $this->generator->toLogin($redirect_on_login, $reauth),
            $status_code
        );
    }
    
    /**
     * NOTE: NEVER use this function with user supplied input.
     * Create a new redirect response to an external URL (no validation).
     * This will also completely bypass any validation inside the OpenRedirectProtectionMiddleware.
     */
    public function away($path, $status = 302) :RedirectResponse
    {
        $response = $this->createRedirectResponse($path, $status);
        return $response->bypassValidation();
    }
    
    public function refresh(int $status = 302) :RedirectResponse
    {
        return $this->createRedirectResponse($this->generator->current(), $status);
    }
    
    public function intended(Request $request, string $fallback = '', int $status = 302) :RedirectResponse
    {
        $from_query = rawurldecode($request->query('intended', ''));
        
        if ($from_query !== '') {
            return $this->to($from_query, $status);
        }
        
        if ($fallback !== '') {
            return $this->to($fallback, $status);
        }
        
        return $this->to('/', $status);
    }
    
    public function previous(int $status = 302, string $fallback = '') :RedirectResponse
    {
        return $this->back($status, $fallback);
    }
    
    public function back(int $status = 302, string $fallback = '') :RedirectResponse
    {
        $previous_url = $this->generator->back($fallback);
        
        return $this->createRedirectResponse($previous_url, $status);
    }
    
    public function guest(string $path, $status = 302, array $query = [], bool $secure = true, bool $absolute = false)
    {
        throw new LogicException(
            'The Redirector::guest method can only be used when sessions are enabled in the config'
        );
    }
    
    protected function validateStatusCode(int $status_code)
    {
        $valid = in_array($status_code, [201, 301, 302, 303, 304, 307, 308]);
        
        if ( ! $valid) {
            throw new LogicException("Status code [{$status_code} is not valid for redirects.]");
        }
    }
    
}