<?php


	namespace WPEmerge\ServiceProviders;

	use Contracts\ContainerAdapter;
	use WPEmerge\AbstractFactory;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\ViewComposers\ViewComposerFactory;

	class FactoryServiceProvider implements \WPEmerge\Contracts\ServiceProviderInterface {


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