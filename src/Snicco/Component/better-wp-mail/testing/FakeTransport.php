<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Testing;

use Closure;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\Envelope;
use Snicco\Component\BetterWPMail\ValueObject\Mailbox;
use Snicco\Component\BetterWPMail\ValueObject\MailboxList;
use Snicco\Component\BetterWPMail\WPMailAPI;

use function count;
use function func_get_args;
use function is_array;
use function is_string;
use function parse_url;
use function sprintf;
use function strval;

final class FakeTransport implements Transport
{
    private WPMailAPI $wp;

    /**
     * @var array<class-string, array<array{0: Email, 1: Envelope}>>
     */
    private $sent_mails = [];

    public function __construct(WPMailAPI $wp = null)
    {
        $this->wp = $wp ?? new WPMailAPI();
    }

    public function send(Email $email, Envelope $envelope): void
    {
        $this->recordMail($email, $envelope);
    }

    public function interceptWordPressEmails(): void
    {
        $this->wp->addFilter('pre_wp_mail', /** @psalm-suppress MixedArgumentTypeCoercion */ function (): bool {
            $args = func_get_args();
            if (! isset($args[1]) || ! is_array($args[1])) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('pre_wp_mail did not receive correct arguments');
                // @codeCoverageIgnoreEnd
            }
            $this->recordWPMail($args[1]);
            return false;
        }, PHP_INT_MAX, 1000);
    }

    public function reset(): void
    {
        $this->sent_mails = [];
    }

    public function assertNotSent(string $email_class): void
    {
        $times = count($this->sentEmailsThatMatchCondition($email_class, fn () => true));

        PHPUnit::assertSame(
            0,
            $times,
            sprintf(
                'Email of type [%s] was sent [%d] %s.',
                $email_class,
                $times,
                $times > 1 ? 'times' : 'time'
            )
        );
    }

    public function assertSentTimes(string $mailable_class, int $expected): void
    {
        $times = count($this->sentEmailsThatMatchCondition($mailable_class, fn () => true));

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
     * @param class-string<Email> $email_class
     * @psalm-suppress UnusedClosureParam
     */
    public function assertSentTo(string $recipient, string $email_class): void
    {
        $expected_recipient = Mailbox::create($recipient);

        $this->assertSent(
            $email_class,
            function (Email $email, Envelope $envelope) use ($expected_recipient): bool {
                return $envelope->recipients()->has($expected_recipient);
            }
        );
    }

    /**
     * @template T
     *
     * @param class-string<T> $email_class
     * @param Closure(T,Envelope):bool|null $closure
     */
    public function assertSent(string $email_class, ?Closure $closure = null): void
    {
        PHPUnit::assertTrue(
            $this->wasSent($email_class),
            sprintf('No email of type [%s] was sent.', $email_class),
        );

        if ($closure) {
            $matching = $this->sentEmailsThatMatchCondition($email_class, $closure);
            $count = count($this->sent_mails[$email_class] ?? []);

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

    /**
     * @param class-string<Email> $email_class
     * @psalm-suppress UnusedClosureParam
     */
    public function assertNotSentTo(string $recipient, string $email_class): void
    {
        $expected_recipient = Mailbox::create($recipient);
        $matching = $this->sentEmailsThatMatchCondition(
            $email_class,
            function (Email $email, Envelope $envelope) use ($expected_recipient): bool {
                return $envelope->recipients()->has($expected_recipient);
            }
        );

        $count = count($matching);
        PHPUnit::assertSame(
            0,
            $count,
            sprintf(
                '[%d] %s of type [%s] %s sent to recipient [%s].',
                $count,
                $count > 1 ? 'emails' : 'email',
                $email_class,
                $count > 1 ? 'were' : 'was',
                $expected_recipient->toString()
            )
        );
    }

    private function recordMail(Email $email, Envelope $envelope): void
    {
        $class = get_class($email);

        $this->sent_mails[$class][] = [$email, $envelope];
    }

    /**
     * @param array{to: string|string[], headers:string|string[], subject:string, message:string, attachments: string|string[]} $attributes
     */
    private function recordWPMail(array $attributes): void
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
        $site_url = $this->wp->siteUrl();
        $site_name = parse_url($site_url, PHP_URL_HOST);
        if (! is_string($site_name)) {
            throw new RuntimeException("Cant parse site url [$site_url].");
        }
        if ('www.' === substr($site_name, 0, 4)) {
            $site_name = substr($site_name, 4);
        }
        $from = 'wordpress@' . (strval($site_name));

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

        $from = $this->wp->applyFiltersStrict('wp_mail_from', $from);
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

        foreach ((array) $attachments as $attachment) {
            $wp_mail = $wp_mail->addAttachment(
                $attachment
            );
        }

        $this->recordMail($wp_mail, new Envelope($from, $recipients));
    }

    /**
     * @return Email[]
     */
    private function sentEmailsThatMatchCondition(string $email_class, Closure $condition): array
    {
        $matching = [];

        if (! isset($this->sent_mails[$email_class])) {
            return [];
        }

        foreach ($this->sent_mails[$email_class] as $mail_data) {
            if ($condition($mail_data[0], $mail_data[1]) === true) {
                $matching[] = $mail_data[0];
            }
        }

        return $matching;
    }

    private function wasSent(string $mailable_class): bool
    {
        return isset($this->sent_mails[$mailable_class]);
    }
}
