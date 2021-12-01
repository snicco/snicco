<?php

declare(strict_types=1);

namespace Snicco\Auth\Mail;

use WP_User;
use Snicco\Support\WP;
use Snicco\Mail\Email;

class ResetPasswordMail extends Email
{
    
    public WP_User $user;
    public string  $site_name;
    public string  $magic_link;
    public int     $expires;
    
    public function __construct(WP_User $user, string $magic_link, $expires)
    {
        $this->user = $user;
        $this->site_name = WP::siteName();
        $this->magic_link = $magic_link;
        $this->expires = $expires;
    }
    
    public function configure()
    {
        $this
            ->subject(sprintf('[%s] Password Reset', WP::siteName()))
            ->htmlTemplate('framework.mail.password-reset');
    }
    
}