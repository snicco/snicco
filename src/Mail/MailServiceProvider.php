<?php


    declare(strict_types = 1);


    namespace WPEmerge\Mail;
    use WPEmerge\Contracts\Mailer;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\SendMailEvent;

    class MailServiceProvider extends ServiceProvider
    {

        public function register() : void
        {
            $this->bindMailer();
            $this->bindConfig();
        }

        public function bootstrap() : void
        {
            // TODO: Implement bootstrap() method.
        }

        private function bindMailer()
        {

            $this->container->singleton(Mailer::class, function () {

                return new WordpressMailer();

            });
        }

        private function bindConfig()
        {

            $this->app->alias('mail', PendingMail::class);
            $this->config->extend('events.listeners', [

                SendMailEvent::class => [
                    [WordpressMailer::class, 'send']
                ]

            ]);

        }

    }