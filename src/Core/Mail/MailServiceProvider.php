<?php

declare(strict_types=1);

namespace Snicco\Core\Mail;

use Snicco\Support\WP;
use Snicco\Mail\MailBuilder;
use Snicco\Application\Config;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Contracts\ServiceProvider;
use Snicco\Mail\Testing\FakeMailBuilder;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Mail\Implementations\WordPressMailer;

class MailServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindMailer();
        $this->bindConfig();
        $this->bindMailBuilder();
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function bindMailer()
    {
        $this->container->singleton(Mailer::class, fn() => new WordPressMailer());
    }
    
    private function bindConfig()
    {
        $this->app->alias('mail', MailBuilder::class);
        $this->config->extend('events.listeners', [
            
            //PendingMail::class => [
            //    [Sender::class,
            //],
        
        ]);
        
        $this->config->extendIfEmpty(
            'mail.from',
            fn() => ['name' => WP::siteName(), 'email' => WP::adminEmail()]
        );
        $this->config->extend('mail.reply_to', fn(Config $config) => $config['mail.from']);
    }
    
    private function bindMailBuilder()
    {
        $this->container->singleton(MailBuilderInterface::class, function () {
            return ($this->app->isRunningUnitTest() && class_exists(FakeMailBuilder::class))
                ? new FakeMailBuilder()
                : new MailBuilder($this->container[Dispatcher::class], $this->container);
        });
    }
    
}