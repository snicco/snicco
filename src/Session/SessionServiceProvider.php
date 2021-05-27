<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use WPEmerge\Contracts\ServiceProvider;

    class SessionServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();
            $this->bindSessionHandler();
            $this->bindSessionStore();
        }

        function bootstrap() : void
        {
        }

        private function bindConfig()
        {

            $this->config->extend('session.enabled', false);
            $this->config->extend('session.cookie', 'wp_mvc_session');
            $this->config->extend('session.table', 'sessions');
            $this->config->extend('session.lottery', [2, 100]);
            $this->config->extend('session.path', '/');
            $this->config->extend('session.domain', null);
            $this->config->extend('session.secure', true);
            $this->config->extend('session.http_only', true);
            $this->config->extend('session.same_site', 'lax');
            $this->config->extend('session.driver', 'database');
            $this->config->extend('session.lifetime', 120);


        }

        private function bindSessionStore()
        {

            $name = $this->config->get('session.driver', 'database');

            $this->container->singleton(SessionStore::class, function () use ($name) {

                return new SessionStore($name, $this->container->make(SessionHandler::class));

            });


        }

        private function bindSessionHandler()
        {


            $this->container->singleton(SessionHandler::class, function () {

                $name = $this->config->get('session.driver', 'database');
                $table = $this->config->get('session.table');
                $lifetime = $this->config->get('session.lifetime');

                if ($name === 'database') {

                    global $wpdb;

                    return new DatabaseSessionHandler($wpdb,$table,$lifetime);

                }

            });


        }

    }