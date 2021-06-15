<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;

    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;

    class EnhancedAuthServiceProvider extends ServiceProvider
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

            add_filter('login_url', function ($url, $redirect_to) {

                /** @var UrlGenerator $url_generator */
                $url_generator = $this->container->make(UrlGenerator::class);

                $query = [];

                if ( $redirect_to !== '' ) {
                    $query['redirect_to'] = $redirect_to;
                }

                return $url_generator->toRoute('login', [
                    'query' => $query
                ]);


            }, 10, 3);

            add_filter('logout_url', function ($url, $redirect_url) {

                /** @var UrlGenerator $url_generator */
                $url_generator = $this->container->make(UrlGenerator::class);

                $redirect = ! empty($redirect_url) ? $redirect_url : '/';

                $url = $url_generator->signedLogout(WP::userId(), $redirect);

                return esc_html( $url );


            }, 10, 3);

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