<?php


    declare(strict_types = 1);


    namespace Snicco\Application;

    use Snicco\ExceptionHandling\Exceptions\ConfigurationException;
    use Snicco\Contracts\ServiceProvider;

    trait LoadsServiceProviders
    {

        /**
         *
         * Register and bootstrap all service providers.
         *
         */
        public function loadServiceProviders() : void
        {

            $user_providers = $this->config->get('app.providers', []);

            $providers = collect(self::CORE_SERVICE_PROVIDERS)->merge($user_providers);

            $providers->each([$this, 'isValid'])
                      ->map([$this, 'instantiate'])
                      ->each([$this, 'register'])
                      ->each([$this, 'bootstrap']);


        }

        /**
         * @param  string  $provider
         *
         * @throws ConfigurationException
         */
        private function isValid(string $provider)
        {

            if ( ! is_subclass_of($provider, ServiceProvider::class)) {
                throw new ConfigurationException(
                    'The following class does not implement '.
                    ServiceProvider::class.': '.$provider
                );
            }

        }

        /**
         * @param  string  $provider
         *
         * @return ServiceProvider
         */
        private function instantiate(string $provider) : ServiceProvider
        {

            /** @var ServiceProvider $provider */
            $provider = new $provider($this->container(), $this->config);
            $provider->setApp($this);

            return $provider;

        }

        private function register(ServiceProvider $provider)
        {

            $provider->register();

        }

        private function bootstrap(ServiceProvider $provider)
        {

            $provider->bootstrap();

        }

    }
