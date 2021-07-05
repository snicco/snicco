<?php


    declare(strict_types = 1);


    namespace Tests\fixtures;

    use WPMvc\Contracts\ServiceProvider;
    use WPMvc\Support\Arr;

    class RoutingDefinitionServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $routes = Arr::wrap($this->config->get('routing.definitions'));

            $routes = array_merge($routes, [TESTS_DIR.DS.'fixtures'.DS.'OtherRoutes']);

            $this->config->set('routing.definitions', $routes);

        }

        function bootstrap() : void
        {
        }

    }