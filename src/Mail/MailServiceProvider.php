<?php


    declare(strict_types = 1);


    namespace Snicco\Mail;

    use BetterWpHooks\Contracts\Dispatcher;
    use Snicco\Contracts\Mailer;
    use Snicco\Contracts\ServiceProvider;
    use Snicco\Events\PendingMail;
    use Snicco\Listeners\SendMail;
    use Snicco\Support\WP;

    class MailServiceProvider extends ServiceProvider
    {

        public function register() : void
        {
            $this->bindMailer();
            $this->bindMailBuilder();
            $this->bindConfig();
        }

        public function bootstrap() : void
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

                PendingMail::class => [
                    SendMail::class
                ]

            ]);

            $this->config->extend('mail.from', ['name' => WP::siteName(), 'email' => WP::adminEmail()]);
            $this->config->extend('mail.reply_to', ['name' =>WP::siteName(), 'email' => WP::adminEmail()]);

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

    }