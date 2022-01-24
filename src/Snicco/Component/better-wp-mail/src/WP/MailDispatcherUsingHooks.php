<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\WP;

use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Contracts\MailEventDispatcher;

use function get_class;

final class MailDispatcherUsingHooks implements MailEventDispatcher
{
    
    private ScopableWP $wp;
    
    public function __construct(ScopableWP $wp)
    {
        $this->wp = $wp;
    }
    
    public function fireSending(SendingEmail $sending_email) :void
    {
        $this->wp->doAction(get_class($sending_email->email), $sending_email);
    }
    
    public function fireSent(EmailWasSent $email_was_sent) :void
    {
        $this->wp->doAction(EmailWasSent::class, $email_was_sent);
    }
    
}