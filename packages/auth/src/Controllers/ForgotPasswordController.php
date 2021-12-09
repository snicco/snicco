<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use WP_User;
use Snicco\Core\Support\WP;
use Snicco\Core\Http\Controller;
use Snicco\View\ViewEngine;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Auth\Traits\ResolvesUser;
use Respect\Validation\Validator as v;
use Snicco\Auth\Mail\ResetPasswordMail;
use Snicco\Core\Http\Responses\RedirectResponse;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Auth\Events\FailedPasswordResetLinkRequest;

class ForgotPasswordController extends Controller
{
    
    use ResolvesUser;
    
    private int                  $expiration;
    private MailBuilderInterface $mail_builder;
    private Dispatcher           $dispatcher;
    
    public function __construct(MailBuilderInterface $mail_builder, Dispatcher $dispatcher, int $expiration = 600)
    {
        $this->expiration = $expiration;
        $this->mail_builder = $mail_builder;
        $this->dispatcher = $dispatcher;
    }
    
    public function create(ViewEngine $view_engine) :string
    {
        return $view_engine->make('framework.auth.forgot-password')->with([
            'post_to' => $this->url->toRoute('auth.forgot.password'),
            'title' => 'Forgot Password | '.WP::siteName(),
        ])->toString();
    }
    
    public function store(Request $request) :RedirectResponse
    {
        $validated = $request->validate([
            'login' => v::notEmpty(),
        ]);
        
        $user = $this->getUserByLogin($validated['login']);
        
        if ($user instanceof WP_User) {
            $magic_link = $this->generateSignedUrl($user);
            
            $this->mail_builder->to($user->user_email)
                               ->send(new ResetPasswordMail($user, $magic_link, $this->expiration));
        }
        else {
            $this->dispatcher->dispatch(
                new FailedPasswordResetLinkRequest($request, $validated['login'])
            );
        }
        
        return $this->response_factory->redirect()
                                      ->toRoute('auth.forgot.password')
                                      ->with('password.reset.processed', true);
    }
    
    private function generateSignedUrl(WP_User $user) :string
    {
        return $this->url->signedRoute(
            'auth.reset.password',
            ['query' => ['id' => $user->ID]],
            $this->expiration,
            true
        );
    }
    
}