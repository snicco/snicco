<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Http\Controller;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Auth\Contracts\AuthConfirmation;
use Snicco\Session\Events\SessionRegenerated;
use Snicco\Auth\Exceptions\FailedAuthConfirmationException;

class ConfirmedAuthSessionController extends Controller
{
    
    private AuthConfirmation $auth_confirmation;
    private int              $duration;
    
    public function __construct(AuthConfirmation $auth_confirmation, int $duration)
    {
        $this->auth_confirmation = $auth_confirmation;
        $this->duration = $duration;
    }
    
    public function create(Request $request)
    {
        return $this->auth_confirmation->viewResponse($request);
    }
    
    /**
     * @throws FailedAuthConfirmationException
     */
    public function store(Request $request) :Response
    {
        
        $confirmed = $this->auth_confirmation->confirm($request);
        
        if ($confirmed !== true) {
            
            throw new FailedAuthConfirmationException(
                "Failed auth confirmation for user [{$request->userId()}]",
                'auth.confirm'
            );
            
        }
        
        $this->confirmAuth($request);
        
        return $request->isExpectingJson()
            ? $this->response_factory->make()->withStatus(200)
            : $this->response_factory->redirect()
                                     ->intended($request, $this->url->toRoute('dashboard'));
        
    }
    
    private function confirmAuth(Request $request) :void
    {
        
        $session = $request->session();
        $session->forget('auth.confirm');
        $session->confirmAuthUntil($this->duration);
        $session->regenerate();
        SessionRegenerated::dispatch([$session]);
        
    }
    
}