<?php

declare(strict_types=1);

namespace Snicco\Core\Mail;

use Snicco\Support\WP;
use Snicco\Mail\MailBuilder;
use Snicco\Application\Config;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\Testing\FakeMailer;
use Snicco\Contracts\ServiceProvider;
use Snicco\Mail\Mailer\WordPressMailer;
use Snicco\Mail\ValueObjects\MailDefaults;
use Snicco\Mail\Contracts\MailEventDispatcher;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\EventDispatcher\Contracts\Dispatcher;

class MailServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindMailer();
        $this->bindConfig();
        $this->bindMailBuilder();
        $this->bindMailDefaults();
        $this->bindMailEventDispatcher();
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
        $this->app->alias('mail', MailBuilder::class);
        $this->config->extendIfEmpty(
            'mail.from',
            fn() => ['name' => WP::siteName(), 'email' => WP::adminEmail()]
        );
        $this->config->extend('mail.reply_to', fn(Config $config) => $config['mail.from']);
    }
    
    private function bindMailBuilder()
    {
        $this->container->singleton(MailBuilderInterface::class, MailBuilder::class);
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
    
}