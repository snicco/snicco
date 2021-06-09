<?php


    declare(strict_types = 1);


    namespace WPEmerge\Mail;

    use BetterWpHooks\Contracts\Dispatcher;
    use WPEmerge\Contracts\Mailer;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\PendingMail;
    use WPEmerge\Facade\WP;
    use WPEmerge\Listeners\SendMail;

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
        }

        private function bindMailer()
        {

            $this->container->singleton(Mailer::class, function () {

                return new WordPressMailer();

            });
        }

        private function bindConfig()
        {

            $this->app->alias('mail', MailBuilder::class);
            $this->config->extend('events.listeners', [

                PendingMail::class => [
                    SendMail::class
                ]

            ]);

            $site_name = WP::siteName();
            $admin_email = WP::adminEmail();

            $this->config->extend('mail.from', ['name' => $site_name, 'email' => $admin_email]);
            $this->config->extend('mail.reply_to', ['name' => $site_name, 'email' => $admin_email]);

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