<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;

    use WPEmerge\Auth\Controllers\ConfirmAuthMagicLinkController;
    use WPEmerge\Auth\Events\GenerateLoginUrl;
    use WPEmerge\Auth\Events\GenerateLogoutUrl;
    use WPEmerge\Auth\Listeners\WpLoginRedirectManager;
    use WPEmerge\Auth\Middleware\AuthUnconfirmed;
    use WPEmerge\Auth\Middleware\ConfirmAuth;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Events\WpInit;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Events\NewLogin;
    use WPEmerge\Session\Events\NewLogout;

    class AuthServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();

            $this->extendRoutes(__DIR__.DIRECTORY_SEPARATOR.'routes');

            $this->bindAuthenticator();

            $this->bindEvents();

            $this->extendViews(__DIR__.DIRECTORY_SEPARATOR.'views');

            $this->bindControllers();

            $this->bindWpSessionToken();

        }

        public function bootstrap() : void
        {
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
                    $this->container->make(ResponseFactory::class),
                    $this->config->get('session.auth_confirmed_lifetime')
                );

            });

            $this->container->singleton(ForgotPasswordController::class, function () {


                return new ForgotPasswordController(
                    $this->container->make(UrlGenerator::class),
                    $this->appKey(),
                );

            });

            $this->container->singleton(ResetPasswordController::class, function () {


                return new ResetPasswordController(
                    $this->container->make(UrlGenerator::class),
                    $this->container->make(ResponseFactory::class),
                    $this->appKey(),
                );

            });

        }

        private function bindConfig()
        {


            $this->config->extend('middleware.aliases', [
                'auth.confirmed' => ConfirmAuth::class,
                'auth.unconfirmed' => AuthUnconfirmed::class,
            ]);
        }

        private function bindWpSessionToken()
        {

            // add_filter('session_token_manager', function () {
            //
            //     return WpSessionToken::class;
            //
            // });

        }



    }