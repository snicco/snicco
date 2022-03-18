<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\Testing\wordpress;

use Closure;
use Codeception\TestCase\WPTestCase;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\ExpectationFailedException;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Testing\WPMail;
use Snicco\Component\BetterWPMail\Tests\Testing\fixtures\Email\TestMail;
use Snicco\Component\BetterWPMail\Tests\Testing\fixtures\Email\TestMail2;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\Envelope;

use function sprintf;

use const PHP_INT_MAX;

/**
 * @internal
 */
final class FakeMailerTest extends WPTestCase
{
    private array $mail_data = [];

    private FakeTransport $fake_transport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mail_data = [];
        $this->fake_transport = new FakeTransport();
    }

    /**
     * @test
     */
    public function no_emails_are_sent_if_the_fake_mailer_is_used(): void
    {
        add_filter('pre_wp_mail', function (?bool $null, array $wp_mail_input): bool {
            $this->assertNull($null);
            $this->mail_data[] = $wp_mail_input;

            return true;
        }, 10, 2);

        $mailer = new Mailer(new FakeTransport());

        $mailer->send((new TestMail())->withTo('calvin@web.de'));

        $this->assertCount(0, $this->mail_data);
    }

    /**
     * @test
     */
    public function wp_mail_emails_can_be_intercepted(): void
    {
        $mailer = new Mailer($fake_transport = new FakeTransport());

        $fake_transport->interceptWordPressEmails();

        $sent = false;
        add_action('phpmailer_init', function () use (&$sent): void {
            $sent = true;
        }, PHP_INT_MAX);

        wp_mail('calvin@web.de', 'subject', 'message');

        $mailer->send((new TestMail())->withTo('calvin@web.de'));

        $this->assertFalse($sent, 'wp_mail function not intercepted.');
    }

    /**
     * @test
     */
    public function test_assert_sent_can_pass(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $mailer->send($this->aValidTestEmail());

        $this->fake_transport->assertSent(TestMail::class);
    }

    /**
     * @test
     */
    public function test_assert_sent_can_fail(): void
    {
        $this->assertFailsWithMessageStarting(
            sprintf('No email of type [%s] was sent.', TestMail::class),
            fn() => $this->fake_transport->assertSent(TestMail::class)
        );
    }

    /**
     * @test
     */
    public function test_assert_sent_can_pass_with_valid_condition(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $email =
            (new TestMail())->withTo('c@web.de')
                ->withSender('m@web.de');

        $mailer->send($email);

        $this->fake_transport->assertSent(
            TestMail::class,
            fn(TestMail $email, Envelope $envelope): bool => $email->to()
                    ->has('c@web.de')
                && 'm@web.de' === $envelope->sender()
                    ->address()
        );
    }

    /**
     * @test
     */
    public function test_assert_sent_can_fail_with_invalid_condition(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $email = (new TestMail())->withTo('m@web.de');

        $mailer->send($email);

        $this->assertFailsWithMessageStarting(
            sprintf(
                'The email [%s] was sent [1] time[s] but no email matched the provided condition.',
                TestMail::class
            ),
            fn() => $this->fake_transport->assertSent(
                TestMail::class,
                fn(TestMail $email): bool => $email->to()
                    ->has('c@web.de')
            )
        );
    }

    /**
     * @test
     */
    public function test_assert_not_sent_can_pass(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $mailer->send($this->aValidTestEmail());

        $this->fake_transport->assertNotSent(TestMail2::class);
    }

    /**
     * @test
     */
    public function test_assert_not_sent_can_fail(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $mailer->send($this->aValidTestEmail());
        $mailer->send($this->aValidTestEmail());

        $this->assertFailsWithMessageStarting(
            sprintf('Email of type [%s] was sent [2] times.', TestMail::class),
            fn() => $this->fake_transport->assertNotSent(TestMail::class)
        );
    }

    /**
     * @test
     */
    public function test_assert_sent_times_can_pass(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $mailer->send($this->aValidTestEmail());
        $mailer->send($this->aValidTestEmail());

        $this->fake_transport->assertSentTimes(TestMail::class, 2);
    }

    /**
     * @test
     */
    public function test_assert_sent_times_can_fail(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $mailer->send($this->aValidTestEmail());
        $mailer->send($this->aValidTestEmail());
        $mailer->send($this->aValidTestEmail());

        $this->assertFailsWithMessageStarting(
            sprintf('Email of type [%s] was sent [3] times. Expected [2] times.', TestMail::class),
            fn() => $this->fake_transport->assertSentTimes(TestMail::class, 2)
        );
    }

    /**
     * @test
     */
    public function test_reset_works(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $this->fake_transport->assertNotSent(TestMail::class);

        $mailer->send($this->aValidTestEmail());

        $this->fake_transport->assertSent(TestMail::class);

        $this->fake_transport->reset();

        $this->fake_transport->assertNotSent(TestMail::class);
    }

    /**
     * @test
     */
    public function test_assert_sent_to_can_pass(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $email = (new TestMail())->withTo('Calvin Alkan <c@web.de>');
        $mailer->send($email);

        $email = (new TestMail())->withTo('Marlon Alkan <m@web.de>');
        $mailer->send($email);

        $this->fake_transport->assertSentTo('m@web.de', TestMail::class);
    }

    /**
     * @test
     */
    public function test_assert_to_can_fail_for_duplicate_sending(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $email = (new TestMail())->withTo('Calvin Alkan <c@web.de>');
        $mailer->send($email);
        $mailer->send($email);

        $this->assertFailsWithMessageStarting(
            '[2] emails were sent that match the provided condition',
            fn() => $this->fake_transport->assertSentTo('c@web.de', TestMail::class)
        );
    }

    /**
     * @test
     */
    public function test_assert_to_can_fail_for_wrong_recipient(): void
    {
        $mailer = new Mailer($this->fake_transport);
        $email = (new TestMail())->withTo('Calvin Alkan <c@web.de>');
        $mailer->send($email);

        $this->assertFailsWithMessageStarting(
            sprintf(
                'The email [%s] was sent [1] time[s] but no email matched the provided condition.',
                TestMail::class
            ),
            fn() => $this->fake_transport->assertSentTo('m@web.de', TestMail::class)
        );
    }

    /**
     * @test
     */
    public function test_assert_not_sent_to_can_pass(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $email = (new TestMail())->withTo('Calvin Alkan <c@web.de>');
        $mailer->send($email);

        $this->fake_transport->assertNotSentTo('m@web.de', TestMail::class);
    }

    /**
     * @test
     */
    public function test_assert_not_sent_to_can_fail(): void
    {
        $mailer = new Mailer($this->fake_transport);

        $email = (new TestMail())->withTo('Calvin Alkan <c@web.de>');
        $mailer->send($email);

        $this->assertFailsWithMessageStarting(
            sprintf('[1] email of type [%s] was sent to recipient [c@web.de].', TestMail::class),
            fn() => $this->fake_transport->assertNotSentTo('c@web.de', TestMail::class)
        );
    }

    /**
     * @test
     */
    public function test_assertions_with_mails_sent_directly_by_wp_mail(): void
    {
        $this->fake_transport->interceptWordPressEmails();

        $this->fake_transport->assertNotSent(WPMail::class);

        wp_mail('calvin@web.de', 'subject', 'message');

        $this->fake_transport->assertSent(WPMail::class);
        $this->fake_transport->assertSentTo('calvin@web.de', WPMail::class);
        $this->fake_transport->assertNotSentTo('marlon@web.de', WPMail::class);
        $this->fake_transport->assertSentTimes(WPMail::class, 1);

        $this->fake_transport->reset();

        wp_mail(['calvin@web.de', 'marlon@web.de'], 'subject', 'message');

        $this->fake_transport->assertSent(WPMail::class);
        $this->fake_transport->assertSentTo('calvin@web.de', WPMail::class);
        $this->fake_transport->assertSentTo('marlon@web.de', WPMail::class);
        $this->fake_transport->assertSentTimes(WPMail::class, 1);

        $this->fake_transport->reset();

        wp_mail('Calvin Alkan <calvin@web.de>', 'subject', 'message');
        $this->fake_transport->assertSentTimes(WPMail::class, 1);
        $this->fake_transport->assertSentTo('Calvin Alkan <calvin@web.de>', WPMail::class);

        $this->fake_transport->reset();
        wp_mail('Calvin Alkan <calvin@web.de>', 'subject', 'message');
        wp_mail('Marlon Alkan <marlon@web.de>', 'subject', 'message');

        $this->fake_transport->assertSentTimes(WPMail::class, 2);
        $this->fake_transport->assertSentTo('calvin@web.de', WPMail::class);
        $this->fake_transport->assertSentTo('marlon@web.de', WPMail::class);
    }

    /**
     * @test
     */
    public function test_assert_sent_with_closure_and_word_press_email(): void
    {
        $this->fake_transport->interceptWordPressEmails();

        $this->fake_transport->assertNotSent(WPMail::class);

        wp_mail(
            'calvin@web.de',
            'subject',
            'message',
            [
                'From: My Company <mycompany@web.de>',
                'Reply-To: Office <office@mycompany.de>',
                'Bcc: Jon Doe <jon@web.de>',
                'Cc: Jane Doe <jane@web.de>',
            ]
        );

        $this->fake_transport->assertSent(WPMail::class, fn(WPMail $email): bool => $email->to()->has('calvin@web.de')
            && $email->cc()
                ->has('Jane Doe <jane@web.de>')
            && $email->bcc()
                ->has('jon@web.de')
            && 'subject' === $email->subject()
            && $email->replyTo()
                ->has('Office <office@mycompany.de>')
            && $email->from()
                ->has('My Company <mycompany@web.de>'));
    }

    private function aValidTestEmail(): Email
    {
        return (new TestMail())->withTo('Calvin Alkan <calvin@web.de>');
    }

    private function assertFailsWithMessageStarting(string $message, Closure $closure): void
    {
        try {
            $closure();
            PHPUnit::fail('The test was expected to fail a PHPUnit assertion.');
        } catch (ExpectationFailedException $e) {
            PHPUnit::assertStringStartsWith(
                $message,
                $e->getMessage(),
                'The test failed but the failure message was not as expected.'
            );
        }
    }
}
