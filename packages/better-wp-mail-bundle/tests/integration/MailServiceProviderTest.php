<?php

declare(strict_types=1);

namespace Tests\BetterWPMailBundle\integration;

use Snicco\Mail\MailBuilder;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\Testing\FakeMailer;
use Snicco\Mail\Mailer\WordPressMailer;
use Snicco\Mail\ValueObjects\MailDefaults;
use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Mail\Contracts\MailEventDispatcher;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\MailBundle\FrameworkMailEventDispatcher;

class MailServiceProviderTest extends FrameworkTestCase
{
    
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
    public function the_fake_mailer_is_used_during_unit_testing()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            FakeMailer::class,
            $first = $this->app[Mailer::class]
        );
        $this->assertInstanceOf(
            FakeMailer::class,
            $second = $this->app[Mailer::class]
        );
        $this->assertSame($first, $second);
    }
    
    /** @test */
    public function the_fake_mailer_is_not_used_in_production()
    {
        $this->withAddedConfig('app.env', 'production');
        $this->bootApp();
        $this->assertInstanceOf(
            WordPressMailer::class,
            $first = $this->app[Mailer::class]
        );
        $this->assertInstanceOf(
            WordPressMailer::class,
            $second = $this->app[Mailer::class]
        );
        $this->assertSame($first, $second);
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
    
    /** @test */
    public function the_default_mail_config_is_bound()
    {
        $this->withAddedConfig('mail.from.name', 'Calvin Alkan');
        $this->withAddedConfig('mail.from.email', 'calvin@foo.de');
        $this->withAddedConfig('mail.reply_to.name', 'My Company');
        $this->withAddedConfig('mail.reply_to.email', 'mycompany@web.de');
        $this->bootApp();
        
        $config = $this->app[MailDefaults::class];
        
        $this->assertInstanceOf(MailDefaults::class, $config);
        
        $this->assertSame('Calvin Alkan <calvin@foo.de>', $config->getFrom()->toString());
        $this->assertSame('My Company <mycompany@web.de>', $config->getReplyTo()->toString());
    }
    
    /** @test */
    public function the_mail_event_dispatcher_is_bound()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            MailEventDispatcher::class,
            $d = $this->app[MailEventDispatcher::class]
        );
        $this->assertInstanceOf(FrameworkMailEventDispatcher::class, $d);
    }
    
}
