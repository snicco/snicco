<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPMail\Email;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Testing\TestableEmail;
use Snicco\Component\BetterWPMail\Testing\WordPressMail;
use Snicco\Component\BetterWPMail\Tests\fixtures\AssertFails;
use Snicco\Bundle\Testing\Concerns\InteractsWithWordpressUsers;

final class FakeMailerTest extends WPTestCase
{
    
    use InteractsWithWordpressUsers;
    use AssertFails;
    
    private array $mail_data = [];
    private FakeTransport $fake_mailer;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->mail_data = [];
        $this->fake_mailer = new FakeTransport();
    }
    
    /** @test */
    public function no_emails_are_sent_if_the_fake_mailer_is_used()
    {
        add_filter('pre_wp_mail', function ($null, array $wp_mail_input) {
            $this->mail_data[] = $wp_mail_input;
            return true;
        }, 10, 2);
        
        $mail_builder = new Mailer(new FakeTransport());
        
        $mail_builder->to('calvin@web.de')->send(new TestMail());
        
        $this->assertCount(0, $this->mail_data);
    }
    
    /** @test */
    public function testAssertSent()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        
        $this->assertFailsWithMessageStarting(
            'No mailable of type ['.TestMail::class.'] sent.',
            function () {
                $this->fake_mailer->assertSent(TestMail::class);
            }
        );
        
        $mail_builder->to('calvin@web.de')->send(new TestMail());
        
        $this->fake_mailer->assertSent(TestMail::class);
    }
    
    /** @test */
    public function testAssertNotSent()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        
        $this->fake_mailer->assertNotSent(TestMail::class);
        
        $mail_builder->to('calvin@web.de')->send(new TestMail());
        
        $this->assertFailsWithMessageStarting(
            'A mailable of type ['.TestMail::class.'] sent.',
            function () {
                $this->fake_mailer->assertNotSent(TestMail::class);
            }
        );
    }
    
    /** @test */
    public function testAssertSentTimes()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        
        $this->assertFailsWithMessageStarting(
            'No mailable of type ['.TestMail::class.'] sent.',
            function () {
                $this->fake_mailer->assertSentTimes(TestMail::class, 1);
            }
        );
        
        // 1 unique emails is sent.
        $mail_builder->to([['calvin@web.de'], ['marlon@web.de']])->send(new TestMail());
        
        $this->assertFailsWithMessageStarting(
            'The mailable ['.TestMail::class.'] was sent [1] time[s].',
            function () {
                $this->fake_mailer->assertSentTimes(TestMail::class, 2);
            }
        );
        
        $this->fake_mailer->assertSentTimes(TestMail::class, 1);
    }
    
    /** @test */
    public function testAssertSentTo()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        $mail_builder->to([['calvin@web.de', 'Calvin Alkan'], ['marlon@web.de']])->send(
            new TestMail()
        );
        
        $this->assertFailsWithMessageStarting(
            'No mailable of type ['.TestMail::class.'] was sent to [calvin@gmail.de].',
            function () {
                $this->fake_mailer->assertSentTo('calvin@gmail.de', TestMail::class);
            }
        );
        
        $this->fake_mailer->assertSentTo('calvin@web.de', TestMail::class);
        
        $this->assertFailsWithMessageStarting(
            'No mailable of type ['.DifferentTestMail::class.'] was sent to [calvin@web.de].',
            function () {
                $this->fake_mailer->assertSentTo('calvin@web.de', DifferentTestMail::class);
            }
        );
        
        $this->fake_mailer->assertSentTo(
            ['name' => 'Calvin Alkan', 'email' => 'calvin@web.de'],
            TestMail::class
        );
        
        $this->assertFailsWithMessageStarting(
            'No mailable of type ['
            .DifferentTestMail::class
            .'] was sent to [Marlon Alkan <marlon@web.de>].',
            function () {
                $this->fake_mailer->assertSentTo(
                    ['name' => 'Marlon Alkan', 'email' => 'marlon@web.de'],
                    DifferentTestMail::class
                );
            }
        
        );
    }
    
    /** @test */
    public function testAssertSentToExact()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        $mail_builder->to([['calvin@web.de', 'Calvin Alkan'], ['marlon@web.de']])->send(
            new TestMail()
        );
        
        // Email only works.
        $this->fake_mailer->assertSentTo(['calvin@web.de', 'Calvin'], TestMail::class);
        
        // With exact the name is also compared.
        $this->assertFailsWithMessageStarting(
            'No mailable of type ['.TestMail::class.'] was sent to [Calvin <calvin@web.de>].',
            function () {
                $this->fake_mailer->assertSentToExact(
                    ['calvin@web.de', 'Calvin'],
                    TestMail::class
                );
            }
        );
    }
    
    /** @test */
    public function testAssertNotSentTo()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        $mail_builder->to([['calvin@web.de', 'Calvin Alkan'], ['marlon@web.de']])->send(
            new TestMail()
        );
        
        $this->assertFailsWithMessageStarting(
            'A mailable of type ['.TestMail::class.'] was sent to [calvin@web.de].',
            function () {
                $this->fake_mailer->assertNotSentTo('calvin@web.de', TestMail::class);
            }
        );
        
        $this->fake_mailer->assertNotSentTo('calvin@web.de', DifferentTestMail::class);
        
        $this->assertFailsWithMessageStarting(
            'A mailable of type ['.TestMail::class.'] was sent to [Marlon Alkan <marlon@web.de>].',
            function () {
                $this->fake_mailer->assertNotSentTo(
                    'Marlon Alkan <marlon@web.de>',
                    TestMail::class
                );
            }
        );
    }
    
    /** @test */
    public function testInterceptWordPressEmails()
    {
        $mail_builder = new Mailer($fake_builder = new FakeTransport());
        
        $fake_builder->interceptWordPressEmails();
        
        $count = 0;
        add_action('phpmailer_init', function () use (&$count) {
            $count++;
        }, PHP_INT_MAX);
        
        wp_mail('calvin@web.de', 'subject', 'message');
        $mail_builder->to('calvin@web.de')->send(new TestMail());
        
        $this->assertSame(0, $count, "wp_mail function not intercepted.");
    }
    
    /** @test */
    public function testReset()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        
        $this->fake_mailer->assertNotSent(TestMail::class);
        
        $mail_builder->to('calvin@web.de')->send(new TestMail());
        
        $this->fake_mailer->assertSent(TestMail::class);
        
        $this->fake_mailer->reset();
        
        $this->fake_mailer->assertNotSent(TestMail::class);
    }
    
    /** @test */
    public function testHasSentWordPressEmail()
    {
        $this->fake_mailer->interceptWordPressEmails();
        
        $this->fake_mailer->assertNotSent(WordPressMail::class);
        
        wp_mail('calvin@web.de', 'subject', 'message');
        
        $this->fake_mailer->assertSent(WordPressMail::class);
        $this->fake_mailer->assertSentTo('calvin@web.de', WordPressMail::class);
        $this->fake_mailer->assertNotSentTo('marlon@web.de', WordPressMail::class);
        $this->fake_mailer->assertSentTimes(WordPressMail::class, 1);
        
        $this->fake_mailer->reset();
        
        wp_mail(['calvin@web.de', 'marlon@web.de'], 'subject', 'message');
        
        $this->fake_mailer->assertSent(WordPressMail::class);
        $this->fake_mailer->assertSentTo('calvin@web.de', WordPressMail::class);
        $this->fake_mailer->assertSentTo('marlon@web.de', WordPressMail::class);
        $this->fake_mailer->assertSentTimes(WordPressMail::class, 1);
        
        $this->fake_mailer->reset();
        
        wp_mail('Calvin Alkan <calvin@web.de>', 'subject', 'message');
        $this->fake_mailer->assertSentTimes(WordPressMail::class, 1);
        $this->fake_mailer->assertSentTo('Calvin Alkan <calvin@web.de>', WordPressMail::class);
        
        $this->fake_mailer->reset();
        wp_mail('Calvin Alkan <calvin@web.de>', 'subject', 'message');
        wp_mail('Marlon Alkan <marlon@web.de>', 'subject', 'message');
        
        $this->fake_mailer->assertSentTimes(WordPressMail::class, 2);
        $this->fake_mailer->assertSentTo('calvin@web.de', WordPressMail::class);
        $this->fake_mailer->assertSentTo('marlon@web.de', WordPressMail::class);
    }
    
    /** @test */
    public function testAssertSentWithClosure()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        
        $mail_builder->to('calvinalkan@web.de')->send(new TestMail());
        $mail_builder->to('marlon@web.de')->send(new TestMail());
        
        $this->assertFailsWithMessageStarting(
            'The mailable ['
            .TestMail::class
            .'] was sent [2] time[s] but none matched the passed condition.',
            function () {
                $this->fake_mailer->assertSent(TestMail::class, function (TestableEmail $mail) {
                    return $mail->hasTo('calvin@web.de');
                });
            }
        );
        
        $this->fake_mailer->assertSent(TestMail::class, function (TestableEmail $mail) {
            return $mail->hasTo('calvinalkan@web.de');
        });
        
        $this->assertFailsWithMessageStarting(
            'The mailable ['
            .TestMail::class
            .'] was sent [2] time[s] but none matched the passed condition.',
            function () {
                $this->fake_mailer->assertSent(TestMail::class, function (TestableEmail $mail) {
                    return $mail->hasTo('calvin@web.de') && $mail->getSubject() === 'bogus';
                });
            }
        );
        
        $this->fake_mailer->assertSent(TestMail::class, function (TestableEmail $mail) {
            return $mail->hasTo('calvinalkan@web.de')
                   && $mail->getFrom()[0]->getName()
                      === 'Calvin INC';
        });
    }
    
    /** @test */
    public function testAssertSentWithClosureCccBcc()
    {
        $mail_builder = new Mailer($this->fake_mailer);
        
        $mail_builder->to('calvinalkan@web.de')
                     ->cc('marlon@web.de')
                     ->bcc('jondoe@web.de')
                     ->send(new TestMail());
        
        $this->fake_mailer->assertSent(TestMail::class, function (TestableEmail $mail) {
            return $mail->hasTo('calvinalkan@web.de')
                   && $mail->hasCC('marlon@web.de')
                   && $mail->hasBcc('jondoe@web.de');
        });
        
        $this->assertFailsWithMessageStarting
        (
            'The mailable ['
            .TestMail::class
            .'] was sent [1] time[s] but none matched the passed condition.',
            function () {
                $this->fake_mailer->assertSent(TestMail::class, function (TestableEmail $mail) {
                    return $mail->hasTo('calvin@web.de')
                           && $mail->hasCC('bogus@web.de')
                           && $mail->hasBcc('jondoe@web.de');
                });
            },
        );
    }
    
    /** @test */
    public function testAssertSentWithClosureAndWordPressEmail()
    {
        $this->fake_mailer->interceptWordPressEmails();
        
        $this->fake_mailer->assertNotSent(WordPressMail::class);
        
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
        
        $this->fake_mailer->assertSent(WordPressMail::class, function (WordPressMail $email) {
            return $email->hasTo('calvin@web.de')
                   && $email->hasCC('Jane Doe <jane@web.de>')
                   && $email->hasBcc('jon@web.de')
                   && $email->getSubject() === 'subject'
                   && $email->getReplyTo()[0]->getName() === 'Office'
                   && $email->getFrom()[0]->toString() === 'My Company <mycompany@web.de>';
        });
    }
    
}

class TestMail extends Email
{
    
    public function configure() :void
    {
        $this->subject('subject')->text('foo')->addFrom('calvincorp@info.de', 'Calvin INC');
    }
    
}

class DifferentTestMail extends Email
{
    
    public function configure() :void
    {
        $this->subject('subject')->text('foo');
    }
    
}