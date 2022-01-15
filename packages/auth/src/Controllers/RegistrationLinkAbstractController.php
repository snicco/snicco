<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\HttpRouting\Http\Responsable;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\Auth\Mail\ConfirmRegistrationEmail;
use Snicco\HttpRouting\Http\AbstractController;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\Auth\Contracts\AbstractRegistrationView;
use Snicco\HttpRouting\Http\Responses\RedirectResponse;

class RegistrationLinkAbstractController extends AbstractController
{
    
    private int $lifetime_in_seconds;
    
    public function __construct($lifetime_in_seconds = 600)
    {
        $this->lifetime_in_seconds = $lifetime_in_seconds;
    }
    
    public function create(Request $request, AbstractRegistrationView $response) :Responsable
    {
        return $response->forRequest($request);
    }
    
    public function store(Request $request, MailBuilderInterface $mail_builder) :RedirectResponse
    {
        $email = $request->input('email', '');
        
        if ( ! (filter_var($email, FILTER_VALIDATE_EMAIL))) {
            return $this->response_factory
                ->redirect()->back()
                ->withErrors(
                    ['email' => 'That email address does not seem to be valid.']
                );
        }
        
        $request->session()->put('registration.email', $email);
        $link = $this->url->signedRoute(
            'auth.accounts.create',
            [],
            $this->lifetime_in_seconds,
            true
        );
        
        $mail_builder->to($email)->send(new ConfirmRegistrationEmail($link));
        
        return $this->response_factory->redirect()->back()
                                      ->withFlashMessages('registration.link.success', true);
    }
    
}