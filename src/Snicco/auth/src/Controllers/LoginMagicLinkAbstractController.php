<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use WP_User;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Mail\MagicLinkLoginMail;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Auth\Events\FailedLoginLinkCreationRequest;
use Snicco\Component\HttpRouting\Http\AbstractController;

class LoginMagicLinkAbstractController extends AbstractController
{
    
    use ResolvesUser;
    
    private MailBuilderInterface $mail_builder;
    private Dispatcher           $dispatcher;
    
    public function __construct(MailBuilderInterface $mail_builder, Dispatcher $dispatcher)
    {
        $this->mail_builder = $mail_builder;
        $this->dispatcher = $dispatcher;
    }
    
    public function store(Request $request) :Response
    {
        $user = $this->getUserByLogin($login = $request->post('login', ''));
        
        if ( ! $user instanceof WP_User) {
            $this->dispatcher->dispatch(new FailedLoginLinkCreationRequest($request, $login));
        }
        else {
            $redirect_to = $request->post('redirect_to');
            
            $magic_link = $this->createMagicLink(
                $user,
                $expiration = 300,
                $redirect_to
            );
            
            $this->mail_builder->to($user)
                               ->send(new MagicLinkLoginMail($user, $magic_link, $expiration));
        }
        
        return $request->isExpectingJson()
            ? $this->response_factory->json(
                ['message' => 'If the credentials match our system we will send you a login link email.']
            )
            : $this->response_factory->redirect()->back(302, $this->url->toRoute('auth.login'))
                                     ->withFlashMessages('login.link.processed', true);
    }
    
    protected function createMagicLink($user, $expiration = 300, string $redirect_to = null) :string
    {
        $args = [
            'query' => array_filter(['user_id' => $user->ID, 'redirect_to' => $redirect_to]),
        ];
        
        return $this->url->signedRoute('auth.login.magic-link', $args, $expiration, true);
    }
    
}