<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Snicco\Mail\Sender;
use Tests\stubs\TestApp;
use Snicco\Mail\MailBuilder;
use Tests\FrameworkTestCase;
use Snicco\Events\PendingMail;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\Testing\FakeMailBuilder;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\Mail\Implementations\WordPressMailer;

class MailServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_mailer_can_be_resolved_correctly()
    {
        $this->bootApp();
        $this->assertInstanceOf(WordPressMailer::class, TestApp::resolve(Mailer::class));
    }
    
    /** @test */
    public function the_mail_builder_is__a_singleton()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            MailBuilder::class,
            $first = $this->app[MailBuilderInterface::class]
        );
        $this->assertInstanceOf(
            MailBuilder::class,
            $second = $this->app[MailBuilderInterface::class]
        );
        $this->assertSame($first, $second);
    }
    
    /** @test */
    public function the_fake_mail_builder_is_used_during_unit_testing()
    {
        $this->withAddedConfig('app.env', 'testing');
        $this->bootApp();
        $this->assertInstanceOf(
            FakeMailBuilder::class,
            $first = $this->app[MailBuilderInterface::class]
        );
        $this->assertInstanceOf(
            FakeMailBuilder::class,
            $second = $this->app[MailBuilderInterface::class]
        );
        $this->assertSame($first, $second);
    }
    
    /** @test */
    public function the_mail_event_is_bound()
    {
        $this->bootApp();
        $listeners = TestApp::config('events.listeners');
        
        $this->assertSame([Sender::class], $listeners[PendingMail::class]);
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
