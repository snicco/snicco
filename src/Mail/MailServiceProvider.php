<?php

declare(strict_types=1);

namespace Snicco\Mail;

use Snicco\Support\WP;
use Snicco\Contracts\Mailer;
use Snicco\Listeners\SendMail;
use Snicco\Events\PendingMail;
use Snicco\Contracts\ServiceProvider;
use BetterWpHooks\Contracts\Dispatcher;

class MailServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindMailer();
        $this->bindMailBuilder();
        $this->bindConfig();
    }
    
    private function bindMailer()
    {
        
        $this->container->singleton(Mailer::class, fn() => new WordPressMailer());
    }
    
    private function bindMailBuilder()
    {
        
        $this->container->singleton(MailBuilder::class, function () {
            
            return new MailBuilder(
                $this->container->make(Dispatcher::class),
                $this->container
            );
            
        });
        
    }
    
    private function bindConfig()
    {
        
        $this->app->alias('mail', MailBuilder::class);
        $this->config->extend('events.listeners', [
            
            PendingMail::class => [
                SendMail::class,
            ],
        
        ]);
        
        $this->config->extend('mail.from', ['name' => WP::siteName(), 'email' => WP::adminEmail()]);
        $this->config->extend(
            'mail.reply_to',
            ['name' => WP::siteName(), 'email' => WP::adminEmail()]
        );
        
    }
    
    public function bootstrap() :void
    {
        //
    }
    
}