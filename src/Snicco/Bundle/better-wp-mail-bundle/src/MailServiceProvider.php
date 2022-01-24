<?php

declare(strict_types=1);

namespace Snicco\MailBundle;

use Snicco\Component\Core\Utils\WP;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\Templating\ViewEngine;
use Snicco\Component\BetterWPMail\Event\MailEvents;
use Snicco\Component\Core\Contracts\ServiceProvider;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Renderer\MailRenderer;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;
use Snicco\Component\BetterWPMail\ValueObjects\MailDefaults;
use Snicco\Component\BetterWPMail\Renderer\AggregateRenderer;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Component\BetterWPMail\Contracts\MailBuilderInterface;

/**
 * @internal
 */
class MailServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindMailer();
        $this->bindConfig();
        $this->bindMailBuilder();
        $this->bindMailDefaults();
        $this->bindMailEventDispatcher();
        $this->bindMailRenderer();
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function bindMailer()
    {
        $this->container->singleton(Mailer::class, function () {
            return $this->app->isRunningUnitTest()
                ? new FakeTransport()
                : new WPMailTransport();
        });
    }
    
    private function bindConfig()
    {
        $this->app->alias('mail', MailBuilderInterface::class);
        $this->config->extendIfEmpty(
            'mail.from',
            fn() => ['name' => WP::siteName(), 'email' => WP::adminEmail()]
        );
        $this->config->extend('mail.reply_to', fn(WritableConfig $config) => $config['mail.from']);
    }
    
    private function bindMailBuilder()
    {
        $this->container->singleton(MailBuilderInterface::class, function () {
            return new Mailer(
                $this->container[Mailer::class],
                $this->container[MailRenderer::class],
                $this->container[MailEvents::class],
                $this->container[MailDefaults::class],
            );
        });
    }
    
    private function bindMailDefaults()
    {
        $this->container->singleton(MailDefaults::class, function () {
            return new MailDefaults(
                $this->config->get('mail.from.email'),
                $this->config->get('mail.from.name'),
                $this->config->get('mail.reply_to.email'),
                $this->config->get('mail.reply_to.name'),
            );
        });
    }
    
    private function bindMailEventDispatcher()
    {
        $this->container->singleton(MailEvents::class, function () {
            return new FrameworkMailEvents(
                $this->container[EventDispatcher::class]
            );
        });
    }
    
    private function bindMailRenderer()
    {
        $this->container->singleton(MailRenderer::class, function () {
            return new AggregateRenderer(
                new ViewBasedMailRenderer($this->container[ViewEngine::class]),
                new FilesystemRenderer()
            );
        });
    }
    
}