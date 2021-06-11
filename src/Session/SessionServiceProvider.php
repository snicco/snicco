<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Slim\Csrf\Guard;
    use WPEmerge\Application\Application;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\Middleware\ConfirmAuth;
    use WPEmerge\Session\Controllers\ConfirmAuthMagicLinkController;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Drivers\DatabaseSessionDriver;
    use WPEmerge\Session\Middleware\AuthUnconfirmed;
    use WPEmerge\Session\Middleware\CsrfMiddleware;
    use WPEmerge\Session\Middleware\ShareSessionWithView;
    use WPEmerge\Session\Middleware\StartSessionMiddleware;
    use WPEmerge\Session\Middleware\ValidateSignature;
    use WPEmerge\Support\Arr;

    class SessionServiceProvider extends ServiceProvider

    {

        public function register() : void
        {

            $this->config->extend('session.enabled', false);

            if ( ! $this->config->get('session.enabled')) {
                return;
            }

            $this->bindConfig();
            $this->extendViews();
            $this->extendRoutes();
            $this->bindSessionHandler();
            $this->bindSessionStore();
            $this->bindSessionMiddleware();
            $this->bindCsrfMiddleware();
            $this->bindCsrfStore();
            $this->bindSlimGuard();
            $this->bindAliases();
            $this->bindEncryptor();
            $this->bindControllers();

        }

        function bootstrap() : void
        {


        }

        private function bindConfig()
        {

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
            $this->config->extend('session.auth_confirmed_lifetime', 180);
            $this->config->extend('session.auth_confirm_on_login', true);

            $this->config->extend('middleware.aliases', [
                'csrf' => CsrfMiddleware::class,
                'signed' => ValidateSignature::class,
                'auth.confirmed' => ConfirmAuth::class,
                'auth.unconfirmed' => AuthUnconfirmed::class,
            ]);

            $this->config->extend('middleware.groups.global', [
                StartSessionMiddleware::class,
                ShareSessionWithView::class,
            ]);

            $this->config->extend('middleware.unique', [
                ShareSessionWithView::class,
            ]);


        }

        private function bindSessionStore()
        {

            $name = $this->config->get('session.cookie');

            $this->container->singleton(Session::class, function () use ($name) {

                $store = null;

                if ($this->config->get('session.encrypt')) {

                    $store = new EncryptedSession(
                        $name,
                        $this->container->make(SessionDriver::class),
                        $this->container->make(EncryptorInterface::class)
                    );

                }
                else {

                    $store = new Session($name, $this->container->make(SessionDriver::class));


                }

                return $store;

            });


        }

        private function bindSessionHandler()
        {

            $this->container->singleton(SessionDriver::class, function () {

                $name = $this->config->get('session.driver', 'database');
                $lifetime = $this->config->get('session.lifetime');

                if ($name === 'database') {

                    global $wpdb;

                    $table = $this->config->get('session.table');

                    return new DatabaseSessionDriver($wpdb, $table, $lifetime);

                }

                if ($name === 'array') {
                    return new ArraySessionDriver($lifetime);
                }


            });


        }

        private function bindSessionMiddleware()
        {

            $this->container->singleton(StartSessionMiddleware::class, function () {

                return new StartSessionMiddleware(
                    $this->container->make(Session::class),
                    $this->config->get('session')
                );

            });
        }

        private function bindAliases()
        {

            /** @var Application $app */
            $app = $this->container->make(Application::class);

            $app->alias('session', Session::class);
            $app->alias('csrfField', CsrfField::class, 'asHtml');
            $app->alias('csrf', CsrfField::class);


        }

        private function bindEncryptor()
        {

            $this->container->singleton(EncryptorInterface::class, function () {

                return new Encryptor($this->config->get('app_key'));

            });
        }

        private function bindCsrfMiddleware()
        {

            $this->container->singleton(CsrfMiddleware::class, function ($c, $args) {

                return new CsrfMiddleware(
                    $this->container->make(Guard::class),
                    empty($args) ? '' : Arr::firstEl($args),
                );

            });
        }

        private function bindCsrfStore()
        {

            $this->container->singleton(CsrfStore::class, function () {

                return new CsrfStore($this->container->make(Session::class));

            });
        }

        private function bindSlimGuard()
        {

            $this->container->singleton(Guard::class, function () {

                $storage = $this->container->make(CsrfStore::class);

                return GuardFactory::create(
                    $this->container->make(ResponseFactory::class),
                    $storage
                );

            });
        }

        private function extendRoutes()
        {

            $routes = Arr::wrap($this->config->get('routing.definitions'));

            $vendor_routes_dir = __DIR__.DIRECTORY_SEPARATOR.'routes';

            $routes = array_merge($routes, Arr::wrap($vendor_routes_dir));

            $this->config->set('routing.definitions', $routes);
        }

        private function extendViews()
        {

            $dir = __DIR__.DIRECTORY_SEPARATOR.'views';
            $views = $this->config->get('views', []);
            $views = array_merge($views, [$dir]);
            $this->config->set('views', $views);

        }

        private function bindControllers()
        {

            $this->container->singleton(ConfirmAuthMagicLinkController::class, function () {

                return new ConfirmAuthMagicLinkController(
                    $this->container->make(ResponseFactory::class),
                    $this->config->get('session.auth_confirmed_lifetime')
                );

            });

            $this->container->singleton(WpLoginSessionController::class, function () {

                return new WpLoginSessionController(
                    $this->container->make(ResponseFactory::class),
                    $this->config->get('session.auth_confirmed_lifetime'),
                    $this->config->get('session.auth_confirm_on_login')
                );

            });
        }


    }