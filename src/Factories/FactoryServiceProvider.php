<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Factories;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Factories\AbstractFactory;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Factories\RouteActionFactory;
	use WPEmerge\Factories\ViewComposerFactory;

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
                    $this->config['controllers'] ?? [],
                    $this->container
                );

            });
        }

        private function bindViewComposerFactory() : void
        {

            $this->container->singleton(ViewComposerFactory::class, function () {

                return new ViewComposerFactory(
                    $this->config['composers'] ?? [],
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