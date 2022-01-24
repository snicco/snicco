<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Mailer;

use Closure;
use WP_Error;
use PHPMailer;
use Snicco\Component\BetterWPMail\WP\ScopableWP;
use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\Contracts\Transport;
use Snicco\Component\BetterWPMail\ValueObjects\Envelope;
use Snicco\Component\BetterWPMail\Contracts\TransportException;
use Snicco\Component\BetterWPMail\Exceptions\WPMailTransportException;

use function trim;

/**
 * @api
 */
final class WPMailTransport implements Transport
{
    
    private ScopableWP $wp;
    
    public function __construct(ScopableWP $wp = null)
    {
        $this->wp = $wp ? : new ScopableWP();
    }
    
    /**
     * @throws WPMailTransportException
     */
    public function send(Email $email, Envelope $envelope) :void
    {
        $failure_callable = $this->handleFailure();
        $just_in_time_callable = $this->justInTimeConfiguration($email, $envelope);
        
        $to = $this->getTo($email, $envelope);
        
        // neither WordPress nor PHPMailer for that matter support setting multiple From headers.
        // So we just default to using the $envelope sender. It's not required to explicitly set the
        // sender on the phpmailer instance since that will be
        // taken care of automatically by phpmailer before sending.
        $from = $this->stringifyAddresses([$envelope->sender()], 'From:');
        
        $reply_to = $this->stringifyAddresses($email->replyTo(), 'Reply-To:');
        
        $ccs = $this->stringifyAddresses($email->getCc(), 'Cc:');
        
        $bcc = $this->stringifyAddresses($email->getBcc(), 'Bcc:');
        
        if ($html = $email->htmlBody()) {
            $content_type = "Content-Type: text/html; charset={$email->htmlCharset()}";
            $message = $html;
        }
        else {
            $message = $email->textBody();
            $content_type = "Content-Type: text/plain; charset={$email->textCharset()}";
        }
        
        if (is_resource($message)) {
            $stream = $message;
            $message = stream_get_contents($stream);
            fclose($stream);
            if ($message === false) {
                throw new WPMailTransportException("Could not read from stream.");
            }
        }
        
        $headers = array_merge(
            $ccs,
            $bcc,
            $reply_to,
            $from,
            [$content_type],
            $email->customHeaders()
        );
        
        try {
            // Don't set attachments here since WordPress only adds attachments by file path. Really?
            $success = $this->wp->mail($to, $email->subject(), $message, $headers, []);
            
            if (false === $success) {
                throw new WPMailTransportException('Could not sent the mail with wp_mail().');
            }
        } catch (PHPMailer\PHPMailer\Exception $e) {
            throw new WPMailTransportException("wp_mail() failure.", $e->getMessage(), $e);
        }
        finally {
            $this->resetPHPMailer();
            $this->wp->removeFilter('wp_mail_failed', $failure_callable, 99999);
            $this->wp->removeFilter('phpmailer_init', $just_in_time_callable, 99999);
        }
    }
    
    /**
     * We want to throw a {@link TransportException} to confirm with the interface.
     * Throwing explicit exceptions we also allow a far better usage for clients since they would
     * have to create their own hook callbacks otherwise.
     */
    protected function handleFailure() :Closure
    {
        $closure = function (WP_Error $error) {
            throw WPMailTransportException::becauseWPMailRaisedErrors($error);
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
    private function justInTimeConfiguration(Email $mail, Envelope $envelope) :Closure
    {
        $closure = function (PHPMailer $php_mailer) use ($mail, $envelope) {
            if (($text = $mail->textBody()) && $mail->htmlBody() !== null) {
                $php_mailer->AltBody = $text;
            }
            if ($priority = $mail->priority()) {
                $php_mailer->Priority = $priority;
            }
            foreach ($mail->attachments() as $attachment) {
                if ($attachment->disposition() === 'inline') {
                    $php_mailer->addStringEmbeddedImage(
                        $attachment->body(),
                        $attachment->cid(),
                        $attachment->name() ?? '',
                        $attachment->encoding(),
                        $attachment->contentType(),
                    );
                    continue;
                }
                
                $php_mailer->addStringAttachment(
                    $attachment->body(),
                    $attachment->name() ?? '',
                    $attachment->encoding(),
                    $attachment->contentType(),
                );
            }
        };
        
        $this->wp->addAction('phpmailer_init', $closure, 99999, 1,);
        
        return $closure;
    }
    
    private function stringifyAddresses(iterable $addresses, string $prefix = '') :array
    {
        $stringify = [];
        
        foreach ($addresses as $address) {
            $stringify[] = trim($prefix.' '.$address->toString());
        }
        
        return $stringify;
    }
    
    // Reset properties that wp_mail does not flush by default();
    private function resetPHPMailer()
    {
        global $phpmailer;
        $phpmailer->Body = '';
        $phpmailer->AltBody = '';
        $phpmailer->Priority = null;
        $phpmailer->clearCustomHeaders();
        $phpmailer->clearReplyTos();
    }
    
    private function getTo(Email $email, Envelope $envelope) :array
    {
        $merged = $email->getCc()->merge($email->getBcc());
        
        $to = [];
        foreach ($envelope->recipients() as $recipient) {
            if ( ! $merged->has($recipient)) {
                $to[] = $recipient;
            }
        }
        
        return $this->stringifyAddresses($to);
    }
    
}