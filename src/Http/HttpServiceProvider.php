<?php


    declare(strict_types = 1);


    namespace BetterWP\Http;

    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\StreamFactoryInterface;
    use BetterWP\Contracts\AbstractRedirector;
    use BetterWP\Contracts\ServiceProvider;
    use BetterWP\Contracts\ViewFactoryInterface;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Routing\Pipeline;
    use BetterWP\Routing\UrlGenerator;
    use BetterWP\Session\Session;
    use BetterWP\Session\StatefulRedirector;

    class HttpServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindKernel();

            $this->bindResponseFactory();

            $this->bindCookies();

            $this->bindRedirector();

        }

        public function bootstrap() : void
        {

            $this->bindRequestEndpoint();
        }

        private function bindKernel()
        {

            $this->container->singleton(HttpKernel::class, function () {

                $kernel = new HttpKernel(

                    $this->container->make(Pipeline::class),
                    $this->container->make(ResponseEmitter::class),

                );

                if ( $this->config->get('middleware.disabled', false ) ) {
                    return $kernel;
                }

                if ($this->config->get('middleware.always_run_global', false) ) {

                    $kernel->alwaysWithGlobalMiddleware($this->config->get('middleware.groups.global', []));

                }

                $kernel->withPriority($this->config->get('middleware.priority', []));

                return $kernel;

            });
        }

        private function bindResponseFactory() : void
        {

            $this->container->singleton(ResponseFactory::class, function () {

                return new ResponseFactory(
                    $this->container->make(ViewFactoryInterface::class),
                    $this->container->make(ResponseFactoryInterface::class),
                    $this->container->make(StreamFactoryInterface::class),
                    $this->container->make(AbstractRedirector::class),
                );

            });


        }

        private function bindCookies()
        {

            $this->container->singleton(Cookies::class, function () {

                $cookies = new Cookies();
                $cookies->setDefaults([
                    'value' => '',
                    'domain' => null,
                    'hostonly' => true,
                    'path' => null,
                    'expires' => null,
                    'secure' => true,
                    'httponly' => false,
                    'samesite' => 'lax',
                ]);

                return $cookies;

            });
        }

        private function bindRedirector()
        {

            $this->container->singleton(AbstractRedirector::class, function () {

                return $this->container->make(Redirector::class);

            });
        }

        private function bindRequestEndpoint()
        {

            $request = $this->currentRequest();

            if ( $endpoints = $this->config->get('routing.api.endpoints', false) ) {

                $this->current_request = $request->withAttribute('_api.endpoints', $endpoints );
                $this->container->instance(Request::class, $this->current_request);

            }

            if ($this->current_request->isApiEndPoint()) {

                $this->config->set('_request_endpoint', 'api');

                return;
            }

            if ($this->current_request->isWpAjax()) {

                $this->config->set('_request_endpoint', 'wp_ajax');

                return;

            }

            if ($this->current_request->isWpAdmin()) {

                $this->config->set('_request_endpoint', 'wp_admin');

                return;

            }

            $this->config->set('_request_endpoint', 'frontend');

        }

    }
