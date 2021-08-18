<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Mail\Mailable;

class PendingMail extends Event
{
    
    public Mailable $mail;
    
    public function __construct(Mailable $mail)
    {
        $this->mail = $mail;
    }
    
}