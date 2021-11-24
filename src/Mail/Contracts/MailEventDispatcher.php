<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\Event\SendingEmail;
use Snicco\Mail\Event\EmailWasSent;

interface MailEventDispatcher
{
    
    public function fireSending(SendingEmail $sending_email) :void;
    
    public function fireSent(EmailWasSent $mail_sent) :void;
    
}