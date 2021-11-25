<?php

declare(strict_types=1);

namespace Snicco\Auth\Mail;

use WP_User;
use Snicco\Mail\Email;

class ConfirmAuthMail extends Email
{
    
    public WP_User $user;
    
    public int $lifetime;
    
    public string $magic_link;
    
    public function __construct(WP_User $user, int $link_lifetime_in_sec, string $magic_link)
    {
        $this->user = $user;
        $this->lifetime = $link_lifetime_in_sec;
        $this->magic_link = $magic_link;
    }
    
    public function configure()
    {
        $this->subject('Your Email Confirmation link.')
             ->htmlTemplate('framework.mail.confirm-auth');
    }
    
}