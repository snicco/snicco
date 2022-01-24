<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Event;

use Snicco\Component\BetterWPMail\ValueObjects\Email;

final class SendingEmail
{
    
    public Email $email;
    
    public function __construct(Email $email)
    {
        $this->email = $email;
    }
    
}