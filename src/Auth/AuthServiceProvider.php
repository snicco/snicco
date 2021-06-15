<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;

    use WPEmerge\Auth\Events\GenerateLoginUrl;
    use WPEmerge\Auth\Events\GenerateLogoutUrl;
    use WPEmerge\Auth\Listeners\WpLoginRedirectManager;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Events\WpInit;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;

    class AuthServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->extendRoutes(__DIR__.DIRECTORY_SEPARATOR.'routes');

            $this->bindAuthenticator();

            $this->bindEvents();

            $this->extendViews(__DIR__.DIRECTORY_SEPARATOR.'views');

            $this->bindForgetPasswordController();
            $this->bindPasswordResetController();

        }

        public function bootstrap() : void
        {
        }

        private function bindEvents()
        {

            $this->config->extend('events.listeners', [
                WpInit::class => [

                    [WpLoginRedirectManager::class, 'redirect']

                ],
                GenerateLoginUrl::class => [

                    [WpLoginRedirectManager::class, 'loginUrl']

                ],
                GenerateLogoutUrl::class => [
                    [WpLoginRedirectManager::class, 'logoutUrl']
                ]
            ]);

            $this->config->extend('events.last', [

                'login_url' => [
                    GenerateLoginUrl::class
                ],
                'logout_url' => [
                    GenerateLogoutUrl::class
                ]

            ]);


        }

        private function bindAuthenticator()
        {

            $this->container->singleton(Authenticator::class, function () {

                return new PasswordAuthenticator();

            });

        }

        private function bindForgetPasswordController()
        {

            $this->container->singleton(ForgotPasswordController::class, function () {


                return new ForgotPasswordController(
                    $this->container->make(UrlGenerator::class),
                    $this->appKey(),
                );

            });

        }

        private function bindPasswordResetController()
        {
            $this->container->singleton(ResetPasswordController::class, function () {


                return new ResetPasswordController(
                    $this->container->make(UrlGenerator::class),
                    $this->container->make(ResponseFactory::class),
                    $this->appKey(),
                );

            });

        }


    }