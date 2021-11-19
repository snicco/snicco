<?php

declare(strict_types=1);

namespace Snicco\Mail\Testing;

use Closure;
use Snicco\Mail\ValueObjects\CC;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\ValueObjects\CCs;
use Snicco\Mail\ValueObjects\BCC;
use Snicco\Mail\ValueObjects\BCCs;
use Snicco\Mail\ValueObjects\From;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\ReplyTo;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Mail\ValueObjects\Recipient;
use Snicco\Mail\ValueObjects\Recipients;
use Snicco\Mail\Contracts\ImmutableEmail;

use function add_filter;

final class FakeMailer implements Mailer
{
    
    /**
     * @var array<string,<array>
     */
    private $sent_mails;
    
    /**
     * @param  ImmutableEmail  $mail
     * @param  Recipients  $recipients
     * @param  CCs  $ccs
     * @param  BCCs  $bcc
     */
    public function send(ImmutableEmail $mail, Recipients $recipients, CCs $ccs, BCCs $bcc) :void
    {
        $this->recordMail($mail, $recipients, $ccs, $bcc);
    }
    
    public function interceptWordPressEmails()
    {
        add_filter('pre_wp_mail', function ($null, $attributes) {
            $this->recordWPMail($attributes);
            
            return false;
        }, PHP_INT_MAX, 1000);
    }
    
    public function reset()
    {
        $this->sent_mails = [];
    }
    
    public function assertSent(string $mailable_class, ?Closure $closure = null)
    {
        PHPUnit::assertTrue(
            $this->wasSent($mailable_class),
            "No mailable of type [$mailable_class] sent."
        );
        
        if ($closure) {
            $sent = $this->getSent($mailable_class, $closure);
            
            $count = count($this->sent_mails[$mailable_class]);
            
            PHPUnit::assertNotEmpty(
                $sent,
                "The mailable [$mailable_class] was sent [$count] time[s] but none matched the passed condition."
            );
        }
    }
    
    public function assertNotSent(string $mailable_class)
    {
        PHPUnit::assertFalse(
            $this->wasSent($mailable_class),
            "A mailable of type [$mailable_class] sent."
        );
    }
    
    public function assertSentTimes(string $mailable_class, int $expected)
    {
        $this->assertSent($mailable_class);
        $count = count($this->sent_mails[$mailable_class]);
        PHPUnit::assertSame(
            $expected,
            $count,
            "The mailable [$mailable_class] was sent [$count] time[s]."
        );
    }
    
    public function assertSentTo($recipient, string $mailable_class)
    {
        $expected_recipient = Address::normalize($recipient, Recipient::class)->formatted();
        
        PHPUnit::assertTrue(
            $this->wasSentTo($mailable_class, $expected_recipient),
            "No mailable of type [".$mailable_class."] was sent to [$expected_recipient]."
        );
    }
    
    public function assertSentToExact($recipient, string $mailable_class)
    {
        $expected_recipient = Address::normalize($recipient, Recipient::class)->formatted();
        PHPUnit::assertTrue(
            $this->wasSentTo($mailable_class, $expected_recipient, false),
            'No mailable of type ['.$mailable_class."] was sent to [$expected_recipient]."
        );
    }
    
    public function assertNotSentTo($recipient, string $mailable_class)
    {
        $expected_recipient = Address::normalize($recipient, Recipient::class)->formatted();
        PHPUnit::assertFalse(
            $this->wasSentTo($mailable_class, $expected_recipient),
            'A mailable of type ['.$mailable_class."] was sent to [$expected_recipient]."
        );
    }
    
    private function wasSent(string $mailable_class) :bool
    {
        return isset($this->sent_mails[$mailable_class]);
    }
    
    /**
     * @param  string  $mailable_class
     * @param  Closure  $condition
     *
     * @return TestableEmail[]
     */
    private function getSent(string $mailable_class, Closure $condition) :array
    {
        $matching = [];
        
        foreach ($this->sent_mails[$mailable_class] as $mail_data) {
            $testable_mail = $this->getTestableMail($mail_data);
            
            if ($condition($testable_mail) === true) {
                $matching[] = $testable_mail;
            }
        }
        
        return $matching;
    }
    
    private function getTestableMail(array $data) :TestableEmail
    {
        return new TestableEmail($data['mail'], $data['recipients'], $data['ccs'], $data['bccs']);
    }
    
    private function wasSentTo(string $mailable_class, $recipient, bool $email_only = true) :bool
    {
        if ( ! $this->wasSent($mailable_class)) {
            return false;
        }
        
        foreach ($this->sent_mails[$mailable_class] as $data) {
            $testable_mail = new TestableEmail(
                $data['mail'],
                $data['recipients'],
                $data['ccs'],
                $data['bccs']
            );
            
            if ($testable_mail->hasTo($recipient, $email_only)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function recordMail(ImmutableEmail $mail, Recipients $recipients, CCs $ccs, BCCs $bcc)
    {
        $class = get_class($mail);
        
        $this->sent_mails[$class][] = [
            'mail' => $mail,
            'recipients' => $recipients,
            'ccs' => $ccs,
            'bccs' => $bcc,
        ];
    }
    
    private function recordWPMail(array $attributes)
    {
        $recipients = [];
        foreach ((array) $attributes['to'] as $recipient) {
            $recipients[] = Address::normalize($recipient, Recipient::class);
        }
        
        $headers = (array) $attributes['headers'];
        $carbon_copies = [];
        $blind_carbon_copies = [];
        
        $from = null;
        $reply_to = null;
        $attachments = [];
        
        foreach (($headers) as $header) {
            if (strpos($header, 'Cc:') !== false) {
                $carbon_copies[] = Address::normalize($header, CC::class);
            }
            if (strpos($header, 'Bcc:') !== false) {
                $blind_carbon_copies[] = Address::normalize($header, BCC::class);
            }
            
            if (strpos($header, 'From:') !== false) {
                $from = Address::normalize($header, From::class);
            }
            if (strpos($header, 'Reply-To:') !== false) {
                $reply_to = Address::normalize($header, ReplyTo::class);
            }
        }
        
        $this->recordMail(
            new WordPressTestMail(
                $attributes['subject'],
                $attributes['message'],
                $from,
                $reply_to,
                $attachments
            ),
            new Recipients(...$recipients),
            new CCs(...$carbon_copies),
            new BCCs(...$blind_carbon_copies)
        );
    }
    
}