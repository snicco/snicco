<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\View\PhpViewFinder;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\View\ViewService;
	use WPEmerge\ViewComposers\ViewComposerCollection;
	use WPEmerge\Factories\ViewComposerFactory;



	class ViewServiceProvider extends ServiceProvider {

		public function register() : void {

			$this->container->instance('composers.globals', new VariableBag() );

			$this->container->singleton( ViewServiceInterface::class, function () {

				return $this->container->make(ViewService::class);

			});

			/** @todo Remove this when BetterWpHooks allows binding to interfaces */
			$this->container->singleton(ViewService::class, function () {

				return new ViewService(
					$this->container->make( ViewEngineInterface::class ),
					$this->container->make( ViewComposerCollection::class ),
					$this->container->make('composers.globals')

				);

			});

			$this->container->singleton( ViewFinderInterface::class, function () {

				return new PhpViewFinder( $this->config->get('views', []) );


			} );

			$this->container->singleton( PhpViewEngine::class, function () {

				return new PhpViewEngine(
					$this->container->make(ViewFinderInterface::class),
				);

			} );

			$this->container->singleton( ViewEngineInterface::class, function () {

				return $this->container->make(PhpViewEngine::class);

			} );

			$this->container->singleton( ViewComposerCollection::class, function () {

				return new ViewComposerCollection(
					$this->container->make(ViewComposerFactory::class),
				);

			} );



		}

		public function bootstrap() : void {
			// Nothing to bootstrap.
		}

	}
