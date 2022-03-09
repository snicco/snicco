<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Transport;

use Closure;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Snicco\Component\BetterWPMail\Exception\CantSendEmail;
use Snicco\Component\BetterWPMail\Exception\CantSendEmailWithWPMail;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\Envelope;
use Snicco\Component\BetterWPMail\ValueObject\MailboxList;
use Snicco\Component\BetterWPMail\WPMailAPI;
use WP_Error;

use function count;
use function trim;

final class WPMailTransport implements Transport
{
    private WPMailAPI $wp;

    public function __construct(WPMailAPI $wp = null)
    {
        $this->wp = $wp ?: new WPMailAPI();
    }

    /**
     * @throws CantSendEmailWithWPMail
     */
    public function send(Email $email, Envelope $envelope): void
    {
        $failure_callable = $this->handleFailure();
        $just_in_time_callable = $this->justInTimeConfiguration($email);

        $to = $this->getTo($email, $envelope);

        // neither WordPress nor PHPMailer for that matter support setting multiple From headers.
        // So we just default to using the $envelope sender. It's not required to explicitly set the
        // sender on the phpmailer instance since that will be
        // taken care of automatically by phpmailer before sending.
        $from = $this->stringifyAddresses(new MailboxList([$envelope->sender()]), 'From:');

        $reply_to = $this->stringifyAddresses($email->replyTo(), 'Reply-To:');

        $ccs = $this->stringifyAddresses($email->cc(), 'Cc:');

        $bcc = $this->stringifyAddresses($email->bcc(), 'Bcc:');

        if ($html = $email->htmlBody()) {
            $content_type = "Content-Type: text/html; charset={$email->htmlCharset()}";
            $message = $html;
        } else {
            $message = $email->textBody();
            $content_type = "Content-Type: text/plain; charset={$email->textCharset()}";
        }

        if (null === $message) {
            $message = '';
        }

        $headers = array_merge(
            $ccs,
            $bcc,
            $reply_to,
            $from,
            [$content_type],
        );

        try {
            // Don't set attachments here since WordPress only adds attachments by file path. Really?
            $success = $this->wp->mail($to, $email->subject(), $message, $headers, []);

            if (false === $success) {
                throw new CantSendEmailWithWPMail('Could not sent the mail with wp_mail().');
            }
        } catch (Exception $e) {
            throw new CantSendEmailWithWPMail('wp_mail() failure.', $e->getMessage(), $e);
        } finally {
            $this->resetPHPMailer();
            $this->wp->removeFilter('wp_mail_failed', $failure_callable, 99999);
            $this->wp->removeFilter('phpmailer_init', $just_in_time_callable, 99999);
        }
    }

    /**
     * We want to throw a {@link CantSendEmail} to confirm with the interface.
     * Throwing explicit exceptions we also allow a far better usage for clients since they would
     * have to create their own hook callbacks otherwise.
     */
    private function handleFailure(): Closure
    {
        $closure = /** @return never */
            function (WP_Error $error) {
                throw CantSendEmailWithWPMail::becauseWPMailRaisedErrors($error);
            };

        $this->wp->addAction('wp_mail_failed', $closure, 99999, 1);

        return $closure;
    }

    /**
     * WordPress fires this action just before sending the mail with the global php mailer.
     * SMTP plugins should also include this filter in order to not break plugins that need it.
     * Here we directly configure the underlying PHPMailer instance which has all the options we
     * need.
     */
    private function justInTimeConfiguration(Email $mail): Closure
    {
        $closure = function (PHPMailer $php_mailer) use ($mail): void {
            if ($priority = $mail->priority()) {
                $php_mailer->Priority = $priority;
            }

            $text = $mail->textBody();
            $html = $mail->htmlBody();
            if ($text && $html) {
                $php_mailer->AltBody = $text;
            }

            $attachments = $mail->attachments();

            if (null === $text && null === $html && count($attachments)) {
                $php_mailer->AllowEmpty = true;
            }

            foreach ($attachments as $attachment) {
                if ('inline' === $attachment->disposition()) {
                    $php_mailer->addStringEmbeddedImage(
                        $attachment->bodyAsString(),
                        $attachment->cid(),
                        $attachment->name(),
                        $attachment->encoding(),
                        $attachment->contentType(),
                    );

                    continue;
                }

                $php_mailer->addStringAttachment(
                    $attachment->bodyAsString(),
                    $attachment->name(),
                    $attachment->encoding(),
                    $attachment->contentType(),
                );
            }

            foreach ($mail->customHeaders() as $name => $value) {
                $php_mailer->addCustomHeader($name, $value);
            }
        };

        $this->wp->addAction('phpmailer_init', $closure, 99999, 1, );

        return $closure;
    }

    /**
     * @return string[]
     */
    private function getTo(Email $email, Envelope $envelope): array
    {
        $merged = $email->cc()->merge($email->bcc());

        $to = [];
        foreach ($envelope->recipients() as $recipient) {
            if (! $merged->has($recipient)) {
                $to[] = $recipient;
            }
        }

        return $this->stringifyAddresses(new MailboxList($to));
    }

    /**
     * @return string[]
     */
    private function stringifyAddresses(MailboxList $addresses, string $prefix = ''): array
    {
        $stringify = [];

        foreach ($addresses as $address) {
            $stringify[] = trim($prefix . ' ' . $address->toString());
        }

        return $stringify;
    }

    /**
     * @psalm-suppress UnnecessaryVarAnnotation
     */
    private function resetPHPMailer(): void
    {
        // Reset properties that wp_mail does not flush by default();
        /** @var PHPMailer $mailer */
        $mailer = $GLOBALS['phpmailer'];
        $mailer->Body = '';
        $mailer->AltBody = '';
        $mailer->Priority = null;
        $mailer->AllowEmpty = false;
        $mailer->clearCustomHeaders();
        $mailer->clearReplyTos();
    }
}
