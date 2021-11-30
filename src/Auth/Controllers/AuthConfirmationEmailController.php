<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use WP_User;
use Snicco\Session\Session;
use Snicco\Http\Controller;
use Snicco\Http\Psr7\Request;
use Snicco\Routing\UrlGenerator;
use Snicco\Traits\InteractsWithTime;
use Snicco\Auth\Mail\ConfirmAuthMail;
use Snicco\Mail\Contracts\MailBuilderInterface;

class AuthConfirmationEmailController extends Controller
{
    
    use InteractsWithTime;
    
    private int                  $cool_of_period;
    private int                  $link_lifetime_in_seconds;
    private MailBuilderInterface $mail_builder;
    
    public function __construct(
        MailBuilderInterface $mail_builder,
        UrlGenerator $url,
        int $cool_of_period = 15,
        $link_lifetime_in_seconds = 300
    ) {
        $this->cool_of_period = $cool_of_period;
        $this->link_lifetime_in_seconds = $link_lifetime_in_seconds;
        $this->mail_builder = $mail_builder;
        $this->url = $url;
    }
    
    public function store(Request $request)
    {
        $user = $request->user();
        $session = $request->session();
        
        if ( ! $this->canRequestAnotherEmail($session)) {
            return $request->isExpectingJson()
                ? $this->response_factory->json(['message' => $this->errorMessage()], 429)
                : $this->response_factory->back()->withErrors([
                    'auth.confirm.email.message' => $this->errorMessage(),
                ]);
        }
        
        $this->sendConfirmationMailTo($user, $session);
        
        return $request->isExpectingJson()
            ? $this->response_factory->make(204)
            : $this->response_factory->redirect()->back();
    }
    
    protected function errorMessage() :string
    {
        return "You have requested too many emails. You can request your next email in $this->cool_of_period seconds.";
    }
    
    private function canRequestAnotherEmail(Session $session) :bool
    {
        $last = $session->get('auth.confirm.email.next', 0);
        
        if ($this->currentTime() < $last) {
            return false;
        }
        
        return true;
    }
    
    private function sendConfirmationMailTo(WP_User $user, Session $session)
    {
        $session->flash('auth.confirm.email.sent', true);
        $session->put('auth.confirm.email.next', $this->availableAt($this->cool_of_period));
        $session->put('auth.confirm.email.cool_off', $this->cool_of_period);
        
        $this->mail_builder->to($user)->send(
            new ConfirmAuthMail(
                $user,
                $this->link_lifetime_in_seconds,
                $this->url->signedRoute(
                    'auth.confirm.magic-link',
                    [],
                    $this->link_lifetime_in_seconds,
                    true
                )
            )
        );
    }
    
}