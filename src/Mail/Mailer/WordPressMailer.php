<?php

declare(strict_types=1);

namespace Snicco\Mail\Mailer;

use Closure;
use WP_Error;
use PHPMailer;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\Envelope;
use Snicco\Mail\Contracts\ImmutableEmail;
use Snicco\Mail\Contracts\TransportException;
use Snicco\Mail\Exceptions\WPMailTransportException;

use function wp_mail;
use function add_action;
use function remove_filter;

/**
 * @internal
 */
final class WordPressMailer implements Mailer
{
    
    /**
     * @param  ImmutableEmail  $email
     * @param  Envelope  $envelope
     *
     * @throws WPMailTransportException
     */
    public function send(ImmutableEmail $email, Envelope $envelope) :void
    {
        $failure_callable = $this->handleFailure();
        $just_in_time_callable = $this->justInTimeConfiguration($email, $envelope);
        
        $to = $this->getTo($email, $envelope);
        
        // neither WordPress nor PHPMailer for that matter support setting multiple From headers.
        // So we just default to using the $envelope sender. It's not required to explicitly set to the
        // sender on the phpmailer instance since that will be
        // taken care of automatically by phpmailer before sending.
        $from = $this->stringifyAddresses([$envelope->getSender()], 'From:');
        
        $reply_to = $this->stringifyAddresses($email->getReplyTo(), 'Reply-To:');
        
        $ccs = $this->stringifyAddresses($email->getCc(), 'Cc:');
        
        $bcc = $this->stringifyAddresses($email->getBcc(), 'Bcc:');
        
        if ($html = $email->getHtmlBody()) {
            $content_type = "Content-Type: text/html; charset={$email->getHtmlCharset()}";
            $message = $html;
        }
        else {
            $message = $email->getTextBody();
            $content_type = "Content-Type: text/plain; charset={$email->getTextCharset()}";
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
            $email->getCustomHeaders()
        );
        
        try {
            // Don't set attachments here since WordPress only adds attachments by file path. Really?
            wp_mail($to, $email->getSubject(), $message, $headers, []);
        } catch (PHPMailer\PHPMailer\Exception $e) {
            throw new WPMailTransportException("wp_mail() failure.", $e->getMessage(), $e);
        }
        finally {
            $this->resetPHPMailer();
            remove_filter('wp_mail_failed', $failure_callable, 99999);
            remove_filter('phpmailer_init', $just_in_time_callable, 99999);
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
        
        add_action('wp_mail_failed', $closure, 99999, 1);
        
        return $closure;
    }
    
    /**
     * WordPress fires this action just before sending the mail with the global php mailer.
     * SMTP plugins should also include this filter in order to not break plugins that need it.
     * Here we directly configure the underlying PHPMailer instance which has all the options we
     * need.
     */
    protected function justInTimeConfiguration(ImmutableEmail $mail, Envelope $envelope) :Closure
    {
        $closure = function (PHPMailer $php_mailer) use ($mail, $envelope) {
            if (($text = $mail->getTextBody()) && $mail->getHtmlBody() !== null) {
                $php_mailer->AltBody = $text;
            }
            if ($priority = $mail->getPriority()) {
                $php_mailer->Priority = $priority;
            }
            foreach ($mail->getAttachments() as $attachment) {
                if ($attachment->getDisposition() === 'inline') {
                    $php_mailer->addStringEmbeddedImage(
                        $attachment->getBody(),
                        $attachment->getContentId(),
                        $attachment->getName() ?? '',
                        $attachment->getEncoding(),
                        $attachment->getContentType(),
                    );
                    continue;
                }
                
                $php_mailer->addStringAttachment(
                    $attachment->getBody(),
                    $attachment->getName() ?? '',
                    $attachment->getEncoding(),
                    $attachment->getContentType(),
                );
            }
        };
        
        add_action('phpmailer_init', $closure, 99999, 1,);
        
        return $closure;
    }
    
    private function stringifyAddresses(array $addresses, string $prefix = '') :array
    {
        return array_map(function (Address $address) use ($prefix) {
            return trim($prefix.' '.$address->toString());
        }, $addresses);
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
    
    private function getTo(ImmutableEmail $email, Envelope $envelope) :array
    {
        $merged = array_merge($email->getCc(), $email->getBcc());
        
        $to = array_filter($envelope->getRecipients(), function (Address $address) use ($merged) {
            return in_array($address, $merged, true) === false;
        });
        
        return $this->stringifyAddresses($to);
    }
    
}