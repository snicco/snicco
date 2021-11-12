<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Tests\stubs\TestApp;
use Tests\FrameworkTestCase;
use Snicco\Contracts\Mailer;
use Snicco\Mail\MailBuilder;
use Snicco\Listeners\SendMail;
use Snicco\Events\PendingMail;
use Snicco\Mail\WordPressMailer;

class MailServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_mailer_can_be_resolved_correctly()
    {
        $this->bootApp();
        $this->assertInstanceOf(WordPressMailer::class, TestApp::resolve(Mailer::class));
    }
    
    /** @test */
    public function the_mail_builder_can_be_resolved_correctly()
    {
        $this->bootApp();
        $this->assertInstanceOf(MailBuilder::class, TestApp::mail());
    }
    
    /** @test */
    public function the_mail_event_is_bound()
    {
        $this->bootApp();
        $listeners = TestApp::config('events.listeners');
        
        $this->assertSame([SendMail::class], $listeners[PendingMail::class]);
    }
    
    /** @test */
    public function the_mail_alias_is_bound()
    {
        $this->bootApp();
        $this->assertInstanceOf(MailBuilder::class, TestApp::mail());
    }
    
    /** @test */
    public function the_from_email_setting_defaults_to_the_site_name_and_admin_email()
    {
        $this->bootApp();
        
        $from = TestApp::config('mail.from');
        
        $this->assertSame('Calvin Alkan', $from['name']);
        $this->assertSame('c@web.de', $from['email']);
        
        $reply_to = TestApp::config('mail.reply_to');
        
        $this->assertSame('Calvin Alkan', $reply_to['name']);
        $this->assertSame('c@web.de', $reply_to['email']);
    }
    
}
