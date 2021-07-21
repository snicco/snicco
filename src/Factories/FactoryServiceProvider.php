<?php


	declare( strict_types = 1 );


	namespace Snicco\Factories;

	use Contracts\ContainerAdapter;
	use Snicco\Contracts\ServiceProvider;
	use Snicco\Factories\AbstractFactory;
	use Snicco\Factories\ConditionFactory;
	use Snicco\Factories\RouteActionFactory;
	use Snicco\Factories\ViewComposerFactory;

	class FactoryServiceProvider extends ServiceProvider {


		public function register() :void  {

			$this->bindRouteActionFactory();

			$this->bindViewComposerFactory();

			$this->bindConditionFactory();

		}

		public function bootstrap() :void  {

		    //

		}

        private function bindRouteActionFactory() : void
        {

            $this->container->singleton(RouteActionFactory::class, function () {

                return new RouteActionFactory(
                    $this->config['routing.controllers'] ?? [],
                    $this->container
                );

            });
        }

        private function bindViewComposerFactory() : void
        {

            $this->container->singleton(ViewComposerFactory::class, function () {

                return new ViewComposerFactory(
                    $this->config['view.composers'] ?? [],
                    $this->container
                );

            });
        }

        private function bindConditionFactory() : void
        {

            $this->container->singleton(ConditionFactory::class, function () {

                return new ConditionFactory(

                    $this->config->get('routing.conditions', []),
                    $this->container,

                );

            });
        }

    }