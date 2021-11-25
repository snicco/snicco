<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Mail\Email;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\Core\Events\EventObjects\CoreEvent;

class PendingMail extends CoreEvent implements Mutable
{
    
    public Email $mail;
    public bool  $sent = false;
    
    public function __construct(Email $mail)
    {
        $this->mail = $mail;
    }
    
}