<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail;

use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Contracts\MailEventDispatcher;

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