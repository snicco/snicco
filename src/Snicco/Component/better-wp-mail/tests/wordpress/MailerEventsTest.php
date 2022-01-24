<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress;

use MockPHPMailer;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\WP\ScopableWP;
use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\BetterWPMail\Mailer\WPMailTransport;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Component\BetterWPMail\WP\MailDispatcherUsingHooks;
use Snicco\Component\BetterWPMail\Contracts\MailEventDispatcher;
use Snicco\Component\BetterWPMail\Tests\fixtures\Email\WelcomeEmail;

final class MailerEventsTest extends WPTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        global $phpmailer;
        
        $phpmailer = new MockPHPMailer(true);
        $phpmailer->mock_sent = [];
    }
    
    /** @test */
    public function an_event_is_dispatched_before_an_email_is_sent()
    {
        $count = 0;
        
        add_filter(
            WelcomeEmail::class,
            function (SendingEmail $email) use (&$count) {
                $count++;
            }
        );
        
        $mailer = new Mailer(
            new WPMailTransport(),
            new FilesystemRenderer(),
            $this->getEventDispatcher()
        );
        
        $email = (new WelcomeEmail())->withTo(
            'c@web.de'
        );
        
        $mailer->send($email);
        
        $this->assertSame(1, $count, "Message event not fired.");
        
        $data = $this->getSentMails()[0];
        $header = $data['header'];
        
        $this->assertStringContainsString('To: c@web.de', $header);
    }
    
    /** @test */
    public function the_mail_can_be_customized()
    {
        add_filter(
            WelcomeEmail::class,
            function (SendingEmail $event) {
                $event->email = $event->email->withHtmlBody('Custom Html');
            }
        );
        
        $mailer = new Mailer(
            new WPMailTransport(),
            new FilesystemRenderer(),
            $this->getEventDispatcher()
        );
        
        $email = (new WelcomeEmail())->withTo(
            'c@web.de'
        )->withHtmlBody('foo');
        $mailer->send($email);
        
        $data = $this->getSentMails()[0];
        $header = $data['header'];
        
        $this->assertStringContainsString('Custom Html', $data['body']);
    }
    
    /** @test */
    public function an_event_is_dispatched_after_an_email_is_sent()
    {
        $count = 0;
        add_action(EmailWasSent::class, function (EmailWasSent $sent_email) use (&$count) {
            $this->assertSame('foo', $sent_email->email()->htmlBody());
            $count++;
        });
        
        $mailer = new Mailer(
            new WPMailTransport(),
            new FilesystemRenderer(),
            $this->getEventDispatcher()
        );
        
        $email = (new WelcomeEmail())->withTo(
            'Calvin Alkan <c@web.de>'
        )->withHtmlBody('foo');
        $mailer->send($email);
        
        $this->assertSame(1, $count, 'Message event not fired.');
        
        $data = $this->getSentMails()[0];
        $header = $data['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
    }
    
    private function getEventDispatcher() :MailEventDispatcher
    {
        return new MailDispatcherUsingHooks(new ScopableWP());
    }
    
    private function getSentMails() :array
    {
        global $phpmailer;
        return $phpmailer->mock_sent;
    }
    
}
