<?php

declare(strict_types=1);

namespace Snicco\Auth\Mail;

use WP_User;
use Snicco\Support\WP;
use Snicco\Mail\Email;

class MagicLinkLoginMail extends Email
{
    
    public WP_User $user;
    public string  $site_name;
    public string  $magic_link;
    public int     $expiration;
    
    public function __construct(WP_User $user, string $magic_link, int $expiration)
    {
        $this->magic_link = $magic_link;
        $this->expiration = $expiration;
        $this->user = $user;
        $this->site_name = WP::siteName();
    }
    
    public function configure()
    {
        $this
            ->subject(sprintf('[%s] Login Link', WP::siteName()))
            ->htmlTemplate('framework.mail.magic-link-login');
    }
    
}