<?php

declare(strict_types=1);

namespace Snicco\Auth\Mail;

use WP_User;
use Snicco\Mail\Mailable;
use Snicco\Routing\UrlGenerator;

class ConfirmAuthMail extends Mailable
{
    
    public WP_User $user;
    
    public int $lifetime;
    
    public function __construct(WP_User $user, int $link_lifetime_in_sec)
    {
        $this->user = $user;
        $this->lifetime = $link_lifetime_in_sec;
    }
    
    public function build(UrlGenerator $generator) :Mailable
    {
        
        return $this
            ->subject('Your Email Confirmation link.')
            ->view('auth-confirm-email')
            ->with([
                'magic_link' => $this->generateSignedUrl($generator),
            ]);
        
    }
    
    private function generateSignedUrl(UrlGenerator $generator) :string
    {
        
        return $generator->signedRoute(
            'auth.confirm.magic-link',
            [],
            $this->lifetime,
            true
        );
        
    }
    
    public function unique() :bool
    {
        return false;
    }
    
}