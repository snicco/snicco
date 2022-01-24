<?php

declare(strict_types=1);

namespace Snicco\MailBundle;

use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\BetterWPMail\Contracts\MailEventDispatcher;

/**
 * @interal
 */
final class FrameworkMailEventDispatcher implements MailEventDispatcher
{
    
    private EventDispatcher $dispatcher;
    
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    public function fireSending(SendingEmail $sending_email) :void
    {
        $this->dispatcher->dispatch(get_class($sending_email->email), $sending_email->email);
    }
    
    public function fireSent(EmailWasSent $mail_mail_sent) :void
    {
        $this->dispatcher->dispatch($mail_mail_sent);
    }
    
}