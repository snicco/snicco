<?php

declare(strict_types=1);

namespace Tests\BetterWPMailBundle\integration;

use Snicco\MailBundle\FrameworkMailEvents;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\BetterWPMailBundle\MailBundleTestCase;
use Snicco\Component\BetterWPMail\Event\MailEvents;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\ValueObjects\MailDefaults;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;
use Snicco\Component\BetterWPMail\Contracts\MailBuilderInterface;

class MailServiceProviderTest extends MaiLBundleTestCase
{
    
    /** @test */
    public function the_mail_builder_is__a_singleton()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            Transport::class,
            $first = $this->app[MailBuilderInterface::class]
        );
        $this->assertInstanceOf(
            Transport::class,
            $second = $this->app[MailBuilderInterface::class]
        );
        $this->assertSame($first, $second);
    }
    
    /** @test */
    public function the_fake_mailer_is_used_during_unit_testing()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            FakeTransport::class,
            $first = $this->app[Transport::class]
        );
        $this->assertInstanceOf(
            FakeTransport::class,
            $second = $this->app[Transport::class]
        );
        $this->assertSame($first, $second);
    }
    
    /** @test */
    public function the_fake_mailer_is_not_used_in_production()
    {
        $this->withAddedConfig('app.env', 'production');
        $this->bootApp();
        $this->assertInstanceOf(
            WPMailTransport::class,
            $first = $this->app[Transport::class]
        );
        $this->assertInstanceOf(
            WPMailTransport::class,
            $second = $this->app[Transport::class]
        );
        $this->assertSame($first, $second);
    }
    
    /** @test */
    public function the_mail_alias_is_bound()
    {
        $this->bootApp();
        $this->assertInstanceOf(Transport::class, TestApp::mail());
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
            MailEvents::class,
            $d = $this->app[MailEvents::class]
        );
        $this->assertInstanceOf(FrameworkMailEvents::class, $d);
    }
    
}
