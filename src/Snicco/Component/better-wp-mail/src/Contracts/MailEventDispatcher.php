<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Contracts;

use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Event\EmailWasSent;

interface MailEventDispatcher
{
    
    public function fireSending(SendingEmail $sending_email) :void;
    
    public function fireSent(EmailWasSent $email_was_sent) :void;
    
}