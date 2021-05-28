<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Slim\Csrf\Guard;
    use WPEmerge\Application\Application;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Encryptor;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Core\ErrorHandlerMiddleware;
    use WPEmerge\Support\Arr;

    class SessionServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();
            $this->bindSessionHandler();
            $this->bindSessionStore();
            $this->bindSessionMiddleware();
            $this->bindCsrfMiddleware();
            $this->bindCsrfStore();
            $this->bindSlimGuard();
            $this->bindAliases();
            $this->bindEncryptor();

        }

        function bootstrap() : void
        {

            if ($this->config->get('session.enabled')) {

                $this->config->extend('middleware.groups.global', [StartSessionMiddleware::class]);


            }

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
            $this->config->extend('session.encrypt', false);

            $this->config->extend('middleware.aliases', [
                'csrf' => CsrfMiddleware::class
            ]);

        }

        private function bindSessionStore()
        {

            $name = $this->config->get('session.cookie');

            $this->container->singleton(SessionStore::class, function () use ($name) {

                $store = null;

                if ( $this->config->get('session.encrypt') ) {

                    $store = new EncryptedStore(
                        $name,
                        $this->container->make(SessionHandler::class),
                        $this->container->make(EncryptorInterface::class)
                    );

                } else {

                    $store = new SessionStore($name, $this->container->make(SessionHandler::class));


                }

                return $store;

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

                    return new DatabaseSessionHandler($wpdb, $table, $lifetime);

                }

            });


        }

        private function bindSessionMiddleware()
        {
            $this->container->singleton(StartSessionMiddleware::class, function () {

                return new StartSessionMiddleware(
                    $this->container->make(SessionStore::class),
                    $this->container->make(Cookies::class),
                    $this->config->get('session')
                );

            });
        }

        private function bindAliases()
        {

            /** @var Application $app */
            $app = $this->container->make(Application::class);

            $app->alias('session', SessionStore::class);
            $app->alias('csrfField', CsrfField::class, 'asHtml');


        }

        private function bindEncryptor()
        {
            $this->container->singleton(EncryptorInterface::class, function () {

                return new Encryptor($this->config->get('app_key'));

            });
        }

        private function bindCsrfMiddleware()
        {

            $this->container->singleton(CsrfMiddleware::class, function ($c, $args ) {


                return new CsrfMiddleware(
                    $this->container->make(ErrorHandlerInterface::class),
                    $this->container->make(Guard::class),
                    Arr::firstEl($args),
                );

            });
        }

        private function bindCsrfStore()
        {
            $this->container->singleton(CsrfStore::class, function () {

                return new CsrfStore($this->container->make(SessionStore::class));

            });
        }

        private function bindSlimGuard()
        {
            $this->container->singleton(Guard::class, function () {


                $storage = $this->container->make(CsrfStore::class);

                return new Guard(
                    $this->container->make(ResponseFactory::class),
                    'csrf',
                    $storage,
                    null,
                    1,
                    40
                );

            });
        }


    }