<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Factories\AbstractFactory;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ViewComposerFactory;

	class FactoryServiceProvider extends ServiceProvider {


		public function register() :void  {

			$this->container->singleton(HandlerFactory::class, function () {

				return new HandlerFactory(
					$this->config['controllers'] ?? [],
					$this->container
				);

			});

			$this->container->singleton(ViewComposerFactory::class, function () {

				return new ViewComposerFactory(
					$this->config['composers'] ?? [],
					$this->container
				);

			});



		}


		public function bootstrap() :void  {


		}

	}