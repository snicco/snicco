<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use WP_User;
use Snicco\Http\Controller;
use Snicco\Mail\MailBuilder;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Mail\MagicLinkLoginMail;
use Snicco\Auth\Events\FailedLoginLinkCreationRequest;

class LoginMagicLinkController extends Controller
{
    
    use ResolvesUser;
    
    public function store(Request $request, MailBuilder $mail_builder) :Response
    {
        $user = $this->getUserByLogin($login = $request->post('login', ''));
        
        if ( ! $user instanceof WP_User) {
            FailedLoginLinkCreationRequest::dispatch([$request, $login]);
        }
        else {
            $redirect_to = $request->post('redirect_to');
            
            $magic_link = $this->createMagicLink(
                $user,
                $expiration = 300,
                $redirect_to
            );
            
            $mail_builder->to($user)
                         ->send(new MagicLinkLoginMail($user, $magic_link, $expiration));
        }
        
        return $request->isExpectingJson()
            ? $this->response_factory->json(
                ['message' => 'If the credentials match our system we will send you a login link email.']
            )
            : $this->response_factory->back($this->url->toRoute('auth.login'))
                                     ->with('login.link.processed', true);
    }
    
    protected function createMagicLink($user, $expiration = 300, string $redirect_to = null) :string
    {
        $args = [
            'query' => array_filter(['user_id' => $user->ID, 'redirect_to' => $redirect_to]),
        ];
        
        return $this->url->signedRoute('auth.login.magic-link', $args, $expiration, true);
    }
    
}