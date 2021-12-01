<?php

declare(strict_types=1);

namespace Snicco\Auth\Mail;

use Snicco\Support\WP;
use Snicco\Mail\Email;

class ConfirmRegistrationEmail extends Email
{
    
    public string $magic_link;
    
    public function __construct(string $magic_link)
    {
        $this->magic_link = $magic_link;
    }
    
    public function configure()
    {
        $this->subject(sprintf("Confirm your registration at %s", WP::siteName()))
             ->htmlTemplate('framework.mail.confirm-registration');
    }
    
}