<?php

declare(strict_types=1);

namespace Snicco\Mail;

use Snicco\Mail\Event\EmailWasSent;
use Snicco\Mail\Event\SendingEmail;
use Snicco\Mail\Contracts\MailEventDispatcher;

final class NullDispatcher implements MailEventDispatcher
{
    
    public function fireSending(SendingEmail $sending_email) :void
    {
        // Do nothing
    }
    
    public function fireSent(EmailWasSent $sent_email) :void
    {
        // Do nothing
    }
    
}