<?php


    declare(strict_types = 1);


    namespace Tests\stubs;

    use WPMvc\Contracts\ServiceProvider;

    class UserServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->container->instance('foo', 'bar');

        }

        public function bootstrap() : void
        {

            $this->container->instance('foo_bootstrapped', 'bar_bootstrapped');
        }

    }