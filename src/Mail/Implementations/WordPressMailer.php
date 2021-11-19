<?php

declare(strict_types=1);

namespace Snicco\Mail\Implementations;

use WP_Error;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\ValueObjects\CCs;
use Snicco\Mail\ValueObjects\BCCs;
use Snicco\Mail\ValueObjects\Recipients;
use Snicco\Mail\Contracts\ImmutableEmail;
use Snicco\Mail\Contracts\TransportException;
use Snicco\Mail\Exceptions\WPMailTransportException;

use function wp_mail;

/**
 * @internal
 */
final class WordPressMailer implements Mailer
{
    
    /**
     * @param  ImmutableEmail  $mail
     * @param  Recipients  $recipients
     * @param  CCs  $ccs
     * @param  BCCs  $bcc
     *
     * @throws TransportException
     */
    public function send(ImmutableEmail $mail, Recipients $recipients, CCs $ccs, BCCs $bcc) :void
    {
        $headers = [];
        $headers[] = "Content-Type: {$mail->getContentType()}; charset=UTF-8";
        $headers[] = "{$mail->getReplyTo()}";
        $headers[] = (string) $mail->getFrom();
        
        foreach ($ccs->getValid()->format() as $cc) {
            $headers[] = $cc;
        }
        foreach ($bcc->getValid()->format() as $bcc) {
            $headers[] = $bcc;
        }
        
        $attachments = [];
        
        foreach ($mail->getAttachments() as $attachment) {
            // WordPres is stupid and still does not support attachment names after 8 years.
            // https://core.trac.wordpress.org/ticket/28407
            $attachments[] = $attachment->getPath();
            unset($attachment);
        }
        
        $data = [
            $recipients->getValid()->format(),
            $mail->getSubject(),
            $mail->getMessage(),
            $headers,
            $attachments,
        ];
        
        add_action('wp_mail_failed', function (WP_Error $error) {
            throw WPMailTransportException::becauseWPMailRaisedErrors($error);
        }, 10, 2);
        
        wp_mail(...$data);
    }
    
}