<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;

    use WPEmerge\Auth\Controllers\ConfirmAuthMagicLinkController;
    use WPEmerge\Auth\Events\GenerateLoginUrl;
    use WPEmerge\Auth\Events\GenerateLogoutUrl;
    use WPEmerge\Auth\Events\SettingAuthCookie;
    use WPEmerge\Auth\Listeners\GenerateNewAuthCookie;
    use WPEmerge\Auth\Listeners\WpLoginRedirectManager;
    use WPEmerge\Auth\Middleware\AuthUnconfirmed;
    use WPEmerge\Auth\Middleware\ConfirmAuth;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Events\WpInit;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Events\NewLogin;
    use WPEmerge\Session\Events\NewLogout;
    use WPEmerge\Session\SessionManager;

    class AuthServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();

            $this->extendRoutes(__DIR__.DIRECTORY_SEPARATOR.'routes');

            $this->extendViews(__DIR__.DIRECTORY_SEPARATOR.'views');

            $this->bindAuthenticator();

            $this->bindEvents();

            $this->bindControllers();

            $this->bindWpSessionToken();

        }

        public function bootstrap() : void
        {
            //
        }

        private function bindEvents()
        {


            $this->config->extend('events.listeners', [
                WpInit::class => [

                    [WpLoginRedirectManager::class, 'redirect'],

                ],
                GenerateLoginUrl::class => [

                    [WpLoginRedirectManager::class, 'loginUrl'],

                ],
                GenerateLogoutUrl::class => [
                    [WpLoginRedirectManager::class, 'logoutUrl'],
                ],
                SettingAuthCookie::class => [
                    GenerateNewAuthCookie::class,
                ],

            ]);

            $this->config->extend('events.mapped', [

                'auth_cookie' => [
                    [SettingAuthCookie::class, 999],
                ],

            ]);

            $this->config->extend('events.last', [
                'login_url' => GenerateLoginUrl::class,
                'logout_url' => GenerateLogoutUrl::class,
            ]);



        }

        private function bindAuthenticator()
        {

            $this->container->singleton(Authenticator::class, function () {

                return new PasswordAuthenticator();

            });

        }

        private function bindControllers()
        {

            $this->container->singleton(ConfirmAuthMagicLinkController::class, function () {

                return new ConfirmAuthMagicLinkController(
                    $this->config->get('session.auth_confirmed_lifetime')
                );

            });


        }

        private function bindConfig()
        {

            $this->config->extend('middleware.aliases', [
                'auth.confirmed' => ConfirmAuth::class,
                'auth.unconfirmed' => AuthUnconfirmed::class,
            ]);

            $this->config->extend('auth.endpoint', 'auth');

            $this->config->extend('routing.api.endpoints', [

                'auth' => $this->config->get('auth.endpoint',),

            ]);

        }

        private function bindWpSessionToken()
        {

            add_filter('session_token_manager', function () {

                /** @var SessionManager $manager */
                $manager = $this->container->make(SessionManager::class);
                // Ugly hack. But there is no other way to get this instance to the class that
                // extends WP_SESSION_TOKENS because of Wordpress not using interfaces or DI:
                global $session_manager;
                $session_manager = $manager;

                global $__request;
                $__request = $this->container->make(Request::class);

                return WpAuthSessionToken::class;

            });

        }



    }