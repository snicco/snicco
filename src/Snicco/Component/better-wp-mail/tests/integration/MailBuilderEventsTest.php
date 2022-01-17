<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\integration;

use MockPHPMailer;
use Snicco\Mail\MailBuilder;
use Snicco\Mail\Event\SendingEmail;
use Snicco\Mail\Event\EmailWasSent;
use Codeception\TestCase\WPTestCase;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\Mailer\WordPressMailer;
use Snicco\Mail\Renderer\FilesystemRenderer;
use Snicco\Mail\Contracts\MailEventDispatcher;
use Tests\BetterWPMail\fixtures\Emails\WelcomeEmail;

final class MailBuilderEventsTest extends WPTestCase
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
        
        add_filter(WelcomeEmail::class, function (WelcomeEmail $email) use (&$count) {
            $count++;
        });
        
        $mail_builder = new MailBuilder(
            new WordPressMailer(),
            new FilesystemRenderer(),
            $this->getEventDispatcher()
        );
        
        $mail_builder->to(['c@web.de', 'Calvin Alkan'])->send(new WelcomeEmail());
        
        $this->assertSame(1, $count, "Message event not fired.");
        
        $data = $this->getSentMails()[0];
        $header = $data['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
    }
    
    /** @test */
    public function the_mail_can_be_customized()
    {
        add_filter(WelcomeEmail::class, function (WelcomeEmail $email) {
            $email->to(Address::create('Marlon Alkan <m@web.de>'));
            $email->html('<h1>Custom Html</h1>');
        });
        
        $mail_builder = new MailBuilder(
            new WordPressMailer(),
            new FilesystemRenderer(),
            $this->getEventDispatcher()
        );
        
        $mail_builder->to(['c@web.de', 'Calvin Alkan'])->send(new WelcomeEmail());
        
        $data = $this->getSentMails()[0];
        $header = $data['header'];
        
        $this->assertStringContainsString('<h1>Custom Html</h1>', $data['body']);
        $this->assertStringContainsString('To: Marlon Alkan <m@web.de>', $header);
        
        $this->assertStringNotContainsString('Calvin Alkan', $header);
    }
    
    /** @test */
    public function an_event_is_dispatched_after_an_email_is_sent()
    {
        $count = 0;
        add_action(EmailWasSent::class, function (EmailWasSent $sent_email) use (&$count) {
            $count++;
        });
        
        $mail_builder = new MailBuilder(
            new WordPressMailer(),
            new FilesystemRenderer(),
            $this->getEventDispatcher()
        );
        
        $mail_builder->to(['c@web.de', 'Calvin Alkan'])->send(new WelcomeEmail());
        
        $this->assertSame(1, $count, 'Message event not fired.');
        
        $data = $this->getSentMails()[0];
        $header = $data['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
    }
    
    private function getEventDispatcher() :TestDispatcher
    {
        return new TestDispatcher();
    }
    
    private function getSentMails() :array
    {
        global $phpmailer;
        return $phpmailer->mock_sent;
    }
    
}

class TestDispatcher implements MailEventDispatcher
{
    
    public function fireSending(SendingEmail $sending_email) :void
    {
        do_action(get_class($sending_email->email), $sending_email->email);
    }
    
    public function fireSent(EmailWasSent $sent_email) :void
    {
        do_action(EmailWasSent::class, $sent_email);
    }
    
}