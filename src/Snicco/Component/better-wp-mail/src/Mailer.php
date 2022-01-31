<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail;

use LogicException;
use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\BetterWPMail\Event\MailEvents;
use Snicco\Component\BetterWPMail\Event\NullEvents;
use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Exception\CantSendEmail;
use Snicco\Component\BetterWPMail\Renderer\AggregateRenderer;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Component\BetterWPMail\Renderer\MailRenderer;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;
use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\ValueObjects\Envelope;
use Snicco\Component\BetterWPMail\ValueObjects\Mailbox;
use Snicco\Component\BetterWPMail\ValueObjects\MailboxList;
use Snicco\Component\BetterWPMail\ValueObjects\MailDefaults;

use function count;
use function iterator_to_array;

/**
 * @api
 */
final class Mailer
{

    private MailDefaults $default_config;
    private Transport $transport;
    private MailRenderer $mail_renderer;
    private MailEvents $event_dispatcher;

    public function __construct(
        ?Transport $transport = null,
        ?MailRenderer $mail_renderer = null,
        ?MailEvents $event_dispatcher = null,
        ?MailDefaults $default_config = null
    ) {
        $this->transport = $transport ?? new WPMailTransport();
        $this->mail_renderer = $mail_renderer ?? new AggregateRenderer(new FilesystemRenderer());
        $this->event_dispatcher = $event_dispatcher ?? new NullEvents();
        $this->default_config = $default_config ?? MailDefaults::fromWordPressSettings();
    }

    /**
     * @throws CantSendEmail
     */
    public function send(Email $mail): void
    {
        $this->fireSendingEvent($event = new SendingEmail($mail));

        $mail = $event->email;
        $mail = $this->prepare($mail);
        $this->validate($mail);

        $envelope = new Envelope(
            $this->determineSender($mail),
            $this->mergeRecipientsFromHeaders($mail)
        );

        $this->transport->send($mail, $envelope);

        $this->fireSentEvent($mail, $envelope);
    }

    private function fireSendingEvent(SendingEmail $event): void
    {
        $this->event_dispatcher->fireSending($event);
    }

    private function prepare(Email $mail): Email
    {
        if ($html_template = $mail->htmlTemplate()) {
            $html = $this->mail_renderer->getMailContent($html_template, $mail->context());
            $mail = $mail->withHtmlBody($html);
        }

        if ($text_template = $mail->textTemplate()) {
            $text = $this->mail_renderer->getMailContent($text_template, $mail->context());
            $mail = $mail->withTextBody($text);
        }

        if (!count($mail->from())) {
            $from = $this->default_config->getFrom();
            $mail = $mail->withFrom($from);
        }

        if (!count($mail->replyTo())) {
            $reply_to = $this->default_config->getReplyTo();
            $mail = $mail->withReplyTo($reply_to);
        }

        return $mail;
    }

    // This has to be mutable in order to allow other third-party developers to customize the email.

    private function validate(Email $mail): void
    {
        if (
            null === $mail->textBody()
            && null === $mail->htmlBody()
            && !count($mail->attachments())
        ) {
            throw new LogicException('An email must have a text or an HTML body or attachments.');
        }

        if (!count($mail->cc()) && !count($mail->to()) && !count($mail->bcc())) {
            throw new LogicException('An email must have a "To", "Cc", or "Bcc" header.');
        }

        if (!count($mail->from()) && null === $mail->sender()) {
            throw new LogicException('An email must have a "From" or a "Sender" header.');
        }
    }

    // We make a clone of the objects so that no hooked listener can modify them and event other
    // listeners that might fire later.

    private function determineSender(Email $mail): ?Mailbox
    {
        if ($sender = $mail->sender()) {
            return $sender;
        }

        if (!empty($from = $mail->from())) {
            return iterator_to_array($from)[0];
        }

        if ($return = $mail->returnPath()) {
            return $return;
        }

        return $this->default_config->getFrom();
    }

    private function mergeRecipientsFromHeaders(Email $mail): MailboxList
    {
        return $mail->to()
            ->merge($mail->cc())
            ->merge($mail->bcc());
    }

    private function fireSentEvent(Email $email, Envelope $envelope)
    {
        $this->event_dispatcher->fireSent(new EmailWasSent($email, $envelope));
    }

}

