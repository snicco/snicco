<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Http\Controller;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Auth\Contracts\AuthConfirmation;
use Snicco\Auth\Events\FailedAuthConfirmation;
use Snicco\Session\Events\SessionWasRegenerated;
use Snicco\EventDispatcher\Contracts\Dispatcher;

class ConfirmedAuthSessionController extends Controller
{
    
    private AuthConfirmation $auth_confirmation;
    private Dispatcher       $events;
    private int              $duration;
    
    public function __construct(AuthConfirmation $auth_confirmation, Dispatcher $events, int $duration)
    {
        $this->auth_confirmation = $auth_confirmation;
        $this->duration = $duration;
        $this->events = $events;
    }
    
    public function create(Request $request)
    {
        return $this->auth_confirmation->viewResponse($request);
    }
    
    public function store(Request $request) :Response
    {
        $confirmed = $this->auth_confirmation->confirm($request);
        
        if ($confirmed !== true) {
            $this->events->dispatch(
                new FailedAuthConfirmation(
                    $request,
                    $request->userId()
                )
            );
            
            return $request->isExpectingJson()
                ? $this->response_factory->json(['message' => 'Invalid credentials.'], 422)
                : $this->response_factory->redirectToRoute('auth.confirm')
                                         ->withErrors(
                                             ['auth.confirmation' => 'We could not authenticate you.']
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
        $this->events->dispatch(new SessionWasRegenerated($session));
    }
    
}