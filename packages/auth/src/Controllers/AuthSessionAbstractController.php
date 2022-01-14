<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Closure;
use Snicco\StrArr\Arr;
use Snicco\Core\Utils\Url;
use Snicco\Session\Session;
use Snicco\Core\Http\Responsable;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Http\AbstractController;
use Snicco\Auth\Responses\LoginResponse;
use Snicco\Auth\Responses\LogoutResponse;
use Snicco\Core\Http\Responses\NullResponse;
use Snicco\Auth\Contracts\AbstractLoginView;
use Snicco\Auth\Contracts\AbstractLoginResponse;
use Snicco\Auth\Responses\SuccessfulLoginResponse;
use Snicco\Core\Middleware\Internal\MiddlewarePipeline;
use Snicco\Core\ExceptionHandling\Exceptions\InvalidSignatureException;

class AuthSessionAbstractController extends AbstractController
{
    
    private array $auth_config;
    
    public function __construct(array $auth_config)
    {
        $this->auth_config = $auth_config;
    }
    
    public function create(Request $request, AbstractLoginView $view_response) :Responsable
    {
        if ($request->boolean('reauth')) {
            $this->resetAuthSession($request->session());
        }
        
        if ($request->boolean('interim-login')) {
            $request->session()->put('is_interim_login', true);
        }
        
        return $view_response->forRequest($request);
    }
    
    public function store(Request $request, MiddlewarePipeline $auth_pipeline, AbstractLoginResponse $responsable)
    {
        /**
         * @todo replace with generic pipeline instead of middleware.
         */
        $response = $auth_pipeline->send($request)
                                  ->through($this->auth_config['through'])
                                  ->then($this->unauthenticated());
        
        if ($response instanceof SuccessfulLoginResponse) {
            $this->parseRedirect($request);
            
            $remember = $response->rememberUser();
            $user = $response->authenticateUser();
            
            return new LoginResponse(
                $this->response_factory->toResponse(
                    $responsable->forRequest($request)->forUser($user)
                ),
                $user,
                $remember
            );
        }
        
        // one authenticator has decided to return a custom failure response.
        if ( ! $response instanceof NullResponse) {
            return $response;
        }
        
        return $request->isExpectingJson()
            ? $this->response_factory->json(['message' => 'Invalid credentials.'], 422)
            : $this->response_factory->redirect()->toLogin()
                                     ->withErrors(
                                         ['login' => 'We could not authenticate you with the provided credentials']
                                     );
    }
    
    public function destroy(Request $request, int $user_id) :Response
    {
        if ($user_id !== $request->userId()) {
            throw new InvalidSignatureException(
                "Suspicious logout attempt with query arg mismatch for logged in user [{$request->userId()}]. Query arg id [$user_id]"
            );
        }
        
        $redirect_to = $request->query('redirect_to', $this->url->toRoute('home'));
        
        $response = $this->response_factory->redirect()->to($redirect_to);
        
        return new LogoutResponse($response);
    }
    
    private function resetAuthSession(Session $session)
    {
        $session->invalidate();
        wp_clear_auth_cookie();
    }
    
    private function parseRedirect(Request $request)
    {
        if ($from_query_string = $request->query('redirect_to')) {
            $redirect_url = Url::rebuild($from_query_string);
        }
        else {
            if ( ! $request->hasHeader('referer')) {
                $redirect_url = $this->url->toRoute('dashboard');
            }
            else {
                parse_str(
                    parse_url($request->getHeaderLine('referer'), PHP_URL_QUERY) ?? '',
                    $query
                );
                $redirect_url = Url::rebuild(
                    Arr::get(
                        $query,
                        'redirect_to',
                        $this->url->toRoute('dashboard')
                    )
                );
            }
        }
        
        $request->session()->setIntendedUrl($redirect_url);
    }
    
    // None of our authenticators where able to authenticate the user.
    // Time to bail.
    private function unauthenticated() :Closure
    {
        return fn() => $this->response_factory->null();
    }
    
}

