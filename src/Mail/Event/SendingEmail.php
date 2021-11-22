<?php

declare(strict_types=1);

namespace Snicco\Mail\Event;

use Snicco\Mail\Email;

final class SendingEmail
{
    
    /**
     * @var Email
     */
    public $email;
    
    public function __construct(Email $email)
    {
        $this->email = $email;
    }
    
}