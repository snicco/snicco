<?php

declare(strict_types=1);

namespace Snicco\Bundle\BetterWPMail;

use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\BetterWPMail\Event\MailEvents;
use Snicco\Component\BetterWPMail\Event\MailEventsUsingWPHooks;
use Snicco\Component\BetterWPMail\Event\NullEvents;
use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\GenericEvent;

use function get_class;

final class MailEventsUsingBetterWPHooks implements MailEvents
{
    private EventDispatcher $dispatcher;

    private MailEvents $mail_events;

    public function __construct(EventDispatcher $dispatcher, bool $expose_event_to_wp = true)
    {
        $this->dispatcher = $dispatcher;

        $this->mail_events = $expose_event_to_wp
            ? new MailEventsUsingWPHooks()
            : new NullEvents();
    }

    public function fireSending(SendingEmail $sending_email): void
    {
        $this->dispatcher->dispatch(new GenericEvent(get_class($sending_email->email), [$sending_email]));
        $this->mail_events->fireSending($sending_email);
    }

    public function fireSent(EmailWasSent $email_was_sent): void
    {
        $this->dispatcher->dispatch($email_was_sent);
        $this->mail_events->fireSent($email_was_sent);
    }
}
