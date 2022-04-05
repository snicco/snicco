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
use Snicco\Component\BetterWPMail\ValueObject\Attachment;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\Envelope;
use Snicco\Component\BetterWPMail\ValueObject\Mailbox;
use Snicco\Component\BetterWPMail\ValueObject\MailboxList;
use Snicco\Component\BetterWPMail\ValueObject\MailDefaults;

use function array_filter;
use function count;
use function get_class;
use function sprintf;
use function strtr;

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
        $mail = $this->prepareAndValidate($mail);

        $envelope = new Envelope($this->determineSender($mail), $this->mergeRecipientsFromHeaders($mail));

        $this->transport->send($mail, $envelope);

        $this->fireSentEvent($mail, $envelope);
    }

    private function fireSendingEvent(SendingEmail $event): void
    {
        $this->event_dispatcher->fireSending($event);
    }

    private function prepareAndValidate(Email $mail): Email
    {
        $html_template = $mail->htmlTemplate();
        if ($html_template) {
            if (! $this->mail_renderer->supports($html_template)) {
                throw new LogicException(
                    sprintf('The mail template renderer does not support html template [%s]', $html_template)
                );
            }

            $html = $this->mail_renderer->render($html_template, $mail->context());
            $mail = $mail->withHtmlBody($html);
        }

        $text_template = $mail->textTemplate();
        if ($text_template) {
            if (! $this->mail_renderer->supports($text_template)) {
                throw new LogicException(
                    sprintf('The mail template renderer does not support text template [%s]', $text_template)
                );
            }

            $text = $this->mail_renderer->render($text_template, $mail->context());
            $mail = $mail->withTextBody($text);
        }

        if (0 === count($mail->from())) {
            $from = $this->default_config->getFrom();
            $mail = $mail->withFrom($from);
        }

        if (0 === count($mail->replyTo())) {
            $reply_to = $this->default_config->getReplyTo();
            $mail = $mail->withReplyTo($reply_to);
        }

        if (null === $mail->textBody() && null === $mail->htmlBody() && [] === $mail->attachments()) {
            throw new LogicException('An email must have a text or an HTML body or attachments.');
        }

        $html_content = $mail->htmlBody();
        $inline_attachments = array_filter(
            $mail->attachments(),
            fn (Attachment $attachment): bool => $attachment->isInline()
        );

        if (null !== $html_content && [] !== $inline_attachments) {
            $replace = [];
            foreach ($inline_attachments as $inline_attachment) {
                $replace['cid:' . $inline_attachment->name()] = 'cid:' . $inline_attachment->cid();
            }

            $replaced_content = strtr($html_content, $replace);
            $mail = $mail->withHtmlBody($replaced_content);
        }

        if (count($mail->cc()) > 0) {
            return $mail;
        }

        if (count($mail->to()) > 0) {
            return $mail;
        }

        if (count($mail->bcc()) > 0) {
            return $mail;
        }

        throw new LogicException('An email must have a "To", "Cc", or "Bcc" header.');
    }

    // We make a clone of the objects so that no hooked listener can modify them and event other
    // listeners that might fire later.
    private function determineSender(Email $mail): Mailbox
    {
        if (($sender = $mail->sender()) !== null) {
            return $sender;
        }

        $from = $mail->from()
            ->toArray();

        if ([] !== $from) {
            return $from[0];
        }

        // @codeCoverageIgnoreStart
        throw new LogicException(sprintf('Cant determine sender for mail [%s].', get_class($mail)));
        // @codeCoverageIgnoreEnd
    }

    private function mergeRecipientsFromHeaders(Email $mail): MailboxList
    {
        return $mail->to()
            ->merge($mail->cc())
            ->merge($mail->bcc());
    }

    private function fireSentEvent(Email $email, Envelope $envelope): void
    {
        $this->event_dispatcher->fireSent(new EmailWasSent($email, $envelope));
    }
}
