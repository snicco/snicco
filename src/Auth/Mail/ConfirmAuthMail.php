<?php

declare(strict_types=1);

namespace Snicco\Auth\Mail;

use WP_User;
use Snicco\Mail\Email;
use Snicco\Routing\UrlGenerator;

class ConfirmAuthMail extends Email
{
    
    public WP_User $user;
    
    public int $lifetime;
    
    public function __construct(WP_User $user, int $link_lifetime_in_sec)
    {
        $this->user = $user;
        $this->lifetime = $link_lifetime_in_sec;
    }
    
    public function configure(UrlGenerator $generator) :Email
    {
        return $this
            ->subject('Your Email Confirmation link.')
            ->view('framework.mail.confirm-auth')
            ->with([
                'magic_link' => $this->generateSignedUrl($generator),
            ]);
    }
    
    public function unique() :bool
    {
        return false;
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
    
}