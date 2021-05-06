<?php


	namespace WPEmerge\ServiceProviders;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Factories\AbstractFactory;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ViewComposerFactory;

	class FactoryServiceProvider implements ServiceProviderInterface {


		public function register( ContainerAdapter $container ) {

			$container->singleton(HandlerFactory::class, function ($c) {

				return new HandlerFactory(
					$c[WPEMERGE_CONFIG_KEY]['controllers'] ?? [],
					$c[WPEMERGE_CONTAINER_ADAPTER]
				);

			});

			$container->singleton(ViewComposerFactory::class, function ($c) {

				return new ViewComposerFactory(
					$c[WPEMERGE_CONFIG_KEY]['composers'] ?? [],
					$c[WPEMERGE_CONTAINER_ADAPTER]
				);

			});



		}


		public function bootstrap( ContainerAdapter $container ) {


		}

	}