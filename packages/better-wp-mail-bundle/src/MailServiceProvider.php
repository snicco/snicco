<?php

declare(strict_types=1);

namespace Snicco\MailBundle;

use Snicco\Core\Support\WP;
use Snicco\View\ViewEngine;
use Snicco\Mail\MailBuilder;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\Testing\FakeMailer;
use Snicco\Mail\Mailer\WordPressMailer;
use Snicco\Mail\Contracts\MailRenderer;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Mail\ValueObjects\MailDefaults;
use Snicco\Mail\Renderer\AggregateRenderer;
use Snicco\Mail\Renderer\FilesystemRenderer;
use Snicco\Core\Configuration\WritableConfig;
use Snicco\Mail\Contracts\MailEventDispatcher;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\EventDispatcher\Contracts\Dispatcher;

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
                ? new FakeMailer()
                : new WordPressMailer();
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
            return new MailBuilder(
                $this->container[Mailer::class],
                $this->container[MailRenderer::class],
                $this->container[MailEventDispatcher::class],
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
        $this->container->singleton(MailEventDispatcher::class, function () {
            return new FrameworkMailEventDispatcher(
                $this->container[Dispatcher::class]
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