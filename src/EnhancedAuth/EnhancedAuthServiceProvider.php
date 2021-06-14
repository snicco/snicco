<?php


    declare(strict_types = 1);


    namespace WPEmerge\EnhancedAuth;

    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\UrlGenerator;

    class EnhancedAuthServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->extendRoutes(__DIR__.DIRECTORY_SEPARATOR.'routes');

            $this->bindAuthenticator();

            $this->bindEvents();


        }


        function bootstrap() : void
        {
        }

        private function bindEvents()
        {

            add_filter('login_url', function ($url, $redirect_to, $force_auth) {

                /** @var UrlGenerator $url_generator */
                $url_generator = $this->container->make(UrlGenerator::class);

                $query = [];

                if ( $redirect_to !== '' ) {
                    $query['redirect_to'] = $redirect_to;
                }

                if ( $force_auth ) {
                    $query['force_auth'] = '1';
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

    }