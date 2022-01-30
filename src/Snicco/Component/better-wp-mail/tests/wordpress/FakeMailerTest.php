<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Testing\WPMail;
use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\ValueObjects\Envelope;
use Snicco\Component\BetterWPMail\Tests\fixtures\AssertFails;
use Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail;
use Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail2;

use function sprintf;
use function iterator_to_array;

use const PHP_INT_MAX;

final class FakeMailerTest extends WPTestCase
{
    
    use AssertFails;
    
    private array $mail_data = [];
    private FakeTransport $fake_transport;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->mail_data = [];
        $this->fake_transport = new FakeTransport();
    }
    
    /** @test */
    public function no_emails_are_sent_if_the_fake_mailer_is_used()
    {
        add_filter('pre_wp_mail', function ($null, array $wp_mail_input) {
            $this->mail_data[] = $wp_mail_input;
            return true;
        }, 10, 2);
        
        $mailer = new Mailer(new FakeTransport());
        
        $mailer->send(
            (new TestMail())->withTo(
                'calvin@web.de'
            )
        );
        
        $this->assertCount(0, $this->mail_data);
    }
    
    /** @test */
    public function wp_mail_emails_can_be_intercepted()
    {
        $mailer = new Mailer($fake_transport = new FakeTransport());
        
        $fake_transport->interceptWordPressEmails();
        
        $count = 0;
        add_action('phpmailer_init', function () use (&$count) {
            $count++;
        }, PHP_INT_MAX);
        
        wp_mail('calvin@web.de', 'subject', 'message');
        
        $mailer->send(
            (new TestMail())->withTo(
                'calvin@web.de'
            )
        );
        
        $this->assertSame(0, $count, 'wp_mail function not intercepted.');
    }
    
    /** @test */
    public function test_assertSent_can_pass()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $mailer->send($this->aValidTestEmail());
        
        $this->fake_transport->assertSent(
            TestMail::class
        );
    }
    
    /** @test */
    public function test_assertSent_can_fail()
    {
        $this->assertFailsWithMessageStarting(
            sprintf(
                'No email of type [%s] was sent.',
                TestMail::class
            ),
            fn() => $this->fake_transport->assertSent(
                TestMail::class
            )
        );
    }
    
    /** @test */
    public function test_assertSent_can_pass_with_valid_condition()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $email =
            (new TestMail())->withTo('c@web.de')
                            ->withSender(
                                'm@web.de'
                            );
        
        $mailer->send($email);
        
        $this->fake_transport->assertSent(
            TestMail::class,
            function (TestMail $email, Envelope $envelope) {
                return $email->to()->has('c@web.de')
                       && $envelope->sender()->address() === 'm@web.de';
            }
        );
    }
    
    /** @test */
    public function test_assertSent_can_fail_with_invalid_condition()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $email = (new TestMail())->withTo(
            'm@web.de'
        );
        
        $mailer->send($email);
        
        $this->assertFailsWithMessageStarting(
            sprintf(
                "The email [%s] was sent [1] time[s] but no email matched the provided condition.",
                TestMail::class
            ),
            fn() => $this->fake_transport->assertSent(
                TestMail::class,
                function (TestMail $email) {
                    return $email->to()->has('c@web.de');
                }
            )
        );
    }
    
    /** @test */
    public function test_assertNotSent_can_pass()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $mailer->send($this->aValidTestEmail());
        
        $this->fake_transport->assertNotSent(
            TestMail2::class
        );
    }
    
    /** @test */
    public function test_assertNotSent_can_fail()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $mailer->send($this->aValidTestEmail());
        $mailer->send($this->aValidTestEmail());
        
        $this->assertFailsWithMessageStarting(
            sprintf(
                'Email of type [%s] was sent [2] times.',
                TestMail::class
            ),
            fn() => $this->fake_transport->assertNotSent(
                TestMail::class
            )
        );
    }
    
    /** @test */
    public function test_assertSentTimes_can_pass()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $mailer->send($this->aValidTestEmail());
        $mailer->send($this->aValidTestEmail());
        
        $this->fake_transport->assertSentTimes(
            TestMail::class,
            2
        );
    }
    
    /** @test */
    public function test_assertSentTimes_can_fail()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $mailer->send($this->aValidTestEmail());
        $mailer->send($this->aValidTestEmail());
        $mailer->send($this->aValidTestEmail());
        
        $this->assertFailsWithMessageStarting(
            sprintf(
                'Email of type [%s] was sent [3] times. Expected [2] times.',
                TestMail::class
            ),
            fn() => $this->fake_transport->assertSentTimes(
                TestMail::class,
                2
            )
        );
    }
    
    /** @test */
    public function test_reset_works()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $this->fake_transport->assertNotSent(
            TestMail::class
        );
        
        $mailer->send($this->aValidTestEmail());
        
        $this->fake_transport->assertSent(
            TestMail::class
        );
        
        $this->fake_transport->reset();
        
        $this->fake_transport->assertNotSent(
            TestMail::class
        );
    }
    
    /** @test */
    public function test_assertSentTo_can_pass()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $email = (new TestMail())->withTo(
            'Calvin Alkan <c@web.de>'
        );
        $mailer->send($email);
        
        $email = (new TestMail())->withTo(
            'Marlon Alkan <m@web.de>'
        );
        $mailer->send($email);
        
        $this->fake_transport->assertSentTo(
            'm@web.de',
            TestMail::class
        );
    }
    
    /** @test */
    public function test_assertTo_can_fail_for_duplicate_sending()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $email = (new TestMail())->withTo(
            'Calvin Alkan <c@web.de>'
        );
        $mailer->send($email);
        $mailer->send($email);
        
        $this->assertFailsWithMessageStarting(
            '[2] emails were sent that match the provided condition',
            fn() => $this->fake_transport->assertSentTo(
                'c@web.de',
                TestMail::class
            )
        );
    }
    
    /** @test */
    public function test_assertTo_can_fail_for_wrong_recipient()
    {
        $mailer = new Mailer($this->fake_transport);
        $email = (new TestMail())->withTo(
            'Calvin Alkan <c@web.de>'
        );
        $mailer->send($email);
        
        $this->assertFailsWithMessageStarting(
            sprintf(
                'The email [%s] was sent [1] time[s] but no email matched the provided condition.',
                TestMail::class
            ),
            fn() => $this->fake_transport->assertSentTo(
                'm@web.de',
                TestMail::class
            )
        );
    }
    
    /** @test */
    public function test_assertNotSentTo_can_pass()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $email = (new TestMail())->withTo(
            'Calvin Alkan <c@web.de>'
        );
        $mailer->send($email);
        
        $this->fake_transport->assertNotSentTo(
            'm@web.de',
            TestMail::class
        );
    }
    
    /** @test */
    public function test_assertNotSentTo_can_fail()
    {
        $mailer = new Mailer($this->fake_transport);
        
        $email = (new TestMail())->withTo(
            'Calvin Alkan <c@web.de>'
        );
        $mailer->send($email);
        
        $this->assertFailsWithMessageStarting(
            sprintf(
                "[1] email of type [%s] was sent to recipient [c@web.de].",
                TestMail::class
            ),
            fn() => $this->fake_transport->assertNotSentTo(
                'c@web.de',
                TestMail::class
            )
        );
    }
    
    /** @test */
    public function test_assertions_with_mails_sent_directly_by_wp_mail()
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
    
    /** @test */
    public function testAssertSentWithClosureAndWordPressEmail()
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
        
        $this->fake_transport->assertSent(WPMail::class, function (WPMail $email) {
            return $email->to()->has('calvin@web.de')
                   && $email->cc()->has('Jane Doe <jane@web.de>')
                   && $email->bcc()->has('jon@web.de')
                   && $email->subject() === 'subject'
                   && iterator_to_array($email->replyTo())[0]->name() === 'Office'
                   && iterator_to_array($email->from())[0]->toString()
                      === 'My Company <mycompany@web.de>';
        });
    }
    
    private function aValidTestEmail() :Email
    {
        return (new TestMail())->withTo(
            'Calvin Alkan <calvin@web.de>'
        );
    }
    
}


