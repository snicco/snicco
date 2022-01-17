<?php

declare(strict_types=1);

namespace Snicco\Mail\Testing;

use Closure;
use WP_User;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\Envelope;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Mail\Contracts\ImmutableEmail;

use function add_filter;

final class FakeMailer implements Mailer
{
    
    /**
     * @var array<string,<array>
     */
    private $sent_mails;
    
    public function send(ImmutableEmail $email, Envelope $envelope) :void
    {
        $this->recordMail($email, $envelope);
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
    
    /**
     * @param  string|WP_User|array<string,<string>  $recipient
     * @param  string  $mailable_class
     */
    public function assertSentTo($recipient, string $mailable_class)
    {
        $expected_recipient = Address::create($recipient)->toString();
        
        PHPUnit::assertTrue(
            $this->wasSentTo($mailable_class, $expected_recipient),
            "No mailable of type [".$mailable_class."] was sent to [$expected_recipient]."
        );
    }
    
    /**
     * @param  string|WP_User|array<string,<string>  $recipient
     */
    public function assertSentToExact($recipient, string $mailable_class)
    {
        $expected_recipient = Address::create($recipient)->toString();
        PHPUnit::assertTrue(
            $this->wasSentTo($mailable_class, $expected_recipient, false),
            'No mailable of type ['.$mailable_class."] was sent to [$expected_recipient]."
        );
    }
    
    /**
     * @param  string|WP_User|array<string,<string>  $recipient
     */
    public function assertNotSentTo($recipient, string $mailable_class)
    {
        $expected_recipient = Address::create($recipient)->toString();
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
    
    private function getTestableMail(array $data)
    {
        $email = $data['email'];
        
        if ($email instanceof WordPressMail) {
            return $email;
        }
        
        return new TestableEmail($email, $data['envelope']);
    }
    
    private function wasSentTo(string $mailable_class, $recipient, bool $email_only = true) :bool
    {
        if ( ! $this->wasSent($mailable_class)) {
            return false;
        }
        
        foreach ($this->sent_mails[$mailable_class] as $data) {
            $testable_mail = $this->getTestableMail($data);
            
            if ($testable_mail->hasTo($recipient, $email_only)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function recordMail($email, Envelope $envelope)
    {
        $class = get_class($email);
        
        $this->sent_mails[$class][] = [
            'email' => $email,
            'envelope' => $envelope,
        ];
    }
    
    private function recordWPMail(array $attributes)
    {
        $to = [];
        foreach ((array) $attributes['to'] as $recipient) {
            $to[] = Address::create($recipient);
        }
        
        $headers = (array) $attributes['headers'];
        $carbon_copies = [];
        $blind_carbon_copies = [];
        
        $reply_to = [];
        $attachments = [];
        $sitename = wp_parse_url(network_home_url(), PHP_URL_HOST);
        if ('www.' === substr($sitename, 0, 4)) {
            $sitename = substr($sitename, 4);
        }
        $from = 'wordpress@'.$sitename;
        
        foreach (($headers) as $header) {
            if (strpos($header, 'Cc:') !== false) {
                preg_match('/\w+:\s(?<value>.+)/', $header, $matches);
                $carbon_copies[] = Address::create($matches['value']);
            }
            if (strpos($header, 'Bcc:') !== false) {
                preg_match('/\w+:\s(?<value>.+)/', $header, $matches);
                $blind_carbon_copies[] = Address::create($matches['value']);
            }
            
            if (strpos($header, 'From:') !== false) {
                preg_match('/\w+:\s(?<value>.+)/', $header, $matches);
                $from = $matches['value'];
            }
            if (strpos($header, 'Reply-To:') !== false) {
                preg_match('/\w+:\s(?<value>.+)/', $header, $matches);
                $reply_to[] = Address::create($matches['value']);
            }
        }
        
        $from = apply_filters('wp_mail_from', $from);
        $from = Address::create($from);
        
        $recipients = array_merge($to, $carbon_copies, $blind_carbon_copies);
        
        $this->recordMail(
            new WordPressMail(
                $attributes['subject'],
                $attributes['message'],
                $to,
                $carbon_copies,
                $blind_carbon_copies,
                [$from],
                $reply_to,
                $attachments
            ),
            new Envelope($from, ...$recipients)
        );
    }
    
}