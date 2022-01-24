<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Testing;

use Closure;
use WP_User;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\BetterWPMail\ScopableWP;
use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\ValueObjects\Mailbox;
use Snicco\Component\BetterWPMail\ValueObjects\Envelope;
use Snicco\Component\BetterWPMail\ValueObjects\MailboxList;

use function count;
use function sprintf;
use function wp_parse_url;

final class FakeTransport implements Transport
{
    
    private ScopableWP $wp;
    
    /**
     * @var array<string,<array>
     */
    private $sent_mails;
    
    public function __construct(ScopableWP $wp = null)
    {
        $this->wp = $wp ?? new ScopableWP();
    }
    
    public function send(Email $email, Envelope $envelope) :void
    {
        $this->recordMail($email, $envelope);
    }
    
    public function interceptWordPressEmails()
    {
        $this->wp->addFilter('pre_wp_mail', function ($null, $attributes) {
            $this->recordWPMail($attributes);
            
            return false;
        }, PHP_INT_MAX, 1000);
    }
    
    public function reset()
    {
        $this->sent_mails = [];
    }
    
    public function assertSent(string $email_class, ?Closure $closure = null)
    {
        PHPUnit::assertTrue(
            $this->wasSent($email_class),
            sprintf('No email of type [%s] was sent.', $email_class),
        );
        
        if ($closure) {
            $matching = $this->sentEmailsThatMatchCondition($email_class, $closure);
            $count = count($this->sent_mails[$email_class]);
            
            PHPUnit::assertNotEmpty(
                $matching,
                sprintf(
                    'The email [%s] was sent [%s] time[s] but no email matched the provided condition.',
                    $email_class,
                    $count
                )
            );
            
            PHPUnit::assertSame(
                1,
                count($matching),
                sprintf(
                    '[%d] emails were sent that match the provided condition.',
                    count($matching)
                )
            );
        }
    }
    
    public function assertNotSent(string $email_class)
    {
        $times = count($this->sentEmailsThatMatchCondition($email_class, fn() => true));
        
        PHPUnit::assertSame(
            0,
            $times,
            sprintf(
                "Email of type [%s] was sent [%d] %s.",
                $email_class,
                $times,
                $times > 1 ? 'times' : 'time'
            )
        );
    }
    
    public function assertSentTimes(string $mailable_class, int $expected)
    {
        $times = count($this->sentEmailsThatMatchCondition($mailable_class, fn() => true));
        
        PHPUnit::assertSame(
            $expected,
            $times,
            sprintf(
                'Email of type [%s] was sent [%d] %s. Expected [%d] %s.',
                $mailable_class,
                $times,
                $times > 1 ? 'times' : 'time',
                $expected,
                $expected > 1 ? 'times' : 'time'
            )
        );
    }
    
    /**
     * @param  string|WP_User|array<string,<string>  $recipient
     * @param  string  $email_class
     */
    public function assertSentTo($recipient, string $email_class)
    {
        $expected_recipient = Mailbox::create($recipient);
        
        $this->assertSent(
            $email_class,
            function (Email $email, Envelope $envelope) use ($expected_recipient) {
                return $envelope->recipients()->has($expected_recipient);
            }
        );
    }
    
    /**
     * @param  string|WP_User|array<string,<string>  $recipient
     */
    public function assertNotSentTo($recipient, string $mailable_class)
    {
        $expected_recipient = Mailbox::create($recipient);
        $matching = $this->sentEmailsThatMatchCondition(
            $mailable_class,
            function (Email $email, Envelope $envelope) use ($expected_recipient) {
                return $envelope->recipients()->has($expected_recipient);
            }
        );
        
        $count = count($matching);
        PHPUnit::assertSame(
            0,
            $count,
            sprintf(
                "[%d] %s of type [%s] %s sent to recipient [%s].",
                $count,
                $count > 1 ? 'emails' : 'email',
                $mailable_class,
                $count > 1 ? 'were' : 'was',
                $expected_recipient->toString()
            )
        );
    }
    
    private function wasSent(string $mailable_class) :bool
    {
        return isset($this->sent_mails[$mailable_class]);
    }
    
    /**
     * @param  string  $email_class
     * @param  Closure  $condition
     *
     * @return Email[]
     */
    private function sentEmailsThatMatchCondition(string $email_class, Closure $condition) :array
    {
        $matching = [];
        
        foreach ($this->sent_mails[$email_class] ?? [] as $mail_data) {
            if ($condition($mail_data['email'], $mail_data['envelope']) === true) {
                $matching[] = $mail_data['email'];
            }
        }
        
        return $matching;
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
            $to[] = Mailbox::create($recipient);
        }
        
        $headers = (array) $attributes['headers'];
        $carbon_copies = [];
        $blind_carbon_copies = [];
        
        $reply_to = [];
        $attachments = $attributes['attachments'] ?? [];
        $site_name = wp_parse_url($this->wp->siteUrl(), PHP_URL_HOST);
        if ('www.' === substr($site_name, 0, 4)) {
            $site_name = substr($site_name, 4);
        }
        $from = 'wordpress@'.$site_name;
        
        foreach (($headers) as $header) {
            if (strpos($header, 'Cc:') !== false) {
                preg_match('/\w+:\s(?<value>.+)/', $header, $matches);
                $carbon_copies[] = Mailbox::create($matches['value']);
            }
            if (strpos($header, 'Bcc:') !== false) {
                preg_match('/\w+:\s(?<value>.+)/', $header, $matches);
                $blind_carbon_copies[] = Mailbox::create($matches['value']);
            }
            
            if (strpos($header, 'From:') !== false) {
                preg_match('/\w+:\s(?<value>.+)/', $header, $matches);
                $from = $matches['value'];
            }
            if (strpos($header, 'Reply-To:') !== false) {
                preg_match('/\w+:\s(?<value>.+)/', $header, $matches);
                $reply_to[] = Mailbox::create($matches['value']);
            }
        }
        
        $from = $this->wp->applyFilters('wp_mail_from', $from);
        $from = Mailbox::create($from);
        
        $wp_mail = new WPMail();
        
        $wp_mail = $wp_mail->withTo($to)
                           ->withSubject($attributes['subject'])
                           ->withHtmlBody($attributes['message'])
                           ->withFrom($from);
        
        $recipients = new MailboxList($to);
        
        if (count($carbon_copies)) {
            $wp_mail = $wp_mail->withCc($carbon_copies);
            $recipients = $recipients->merge($carbon_copies);
        }
        if (count($blind_carbon_copies)) {
            $wp_mail = $wp_mail->withBcc($blind_carbon_copies);
            $recipients = $recipients->merge($blind_carbon_copies);
        }
        if (count($reply_to)) {
            $wp_mail = $wp_mail->withReplyTo($reply_to);
        }
        
        foreach ($attachments as $attachment) {
            $wp_mail->addAttachment(
                $attachment
            );
        }
        
        $this->recordMail($wp_mail, new Envelope($from, $recipients));
    }
    
}