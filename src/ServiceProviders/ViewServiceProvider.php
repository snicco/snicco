<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use Illuminate\Config\Repository;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\View\PhpViewFinder;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\View\ViewService;

	use WPEmerge\ViewComposers\ViewComposerCollection;
	use WPEmerge\Factories\ViewComposerFactory;

	use function get_stylesheet_directory;
	use function get_template_directory;


	/**
	 * Provide view dependencies
	 *
	 */
	class ViewServiceProvider extends ServiceProvider {


		public function register() : void {

			/** @todo Refactor to custom class without wp functions */
			$this->config->extend('views', [
				get_stylesheet_directory(),
				get_template_directory(),
			]);

			$this->container->instance('composers.globals', new VariableBag() );

			$this->container->singleton( ViewServiceInterface::class, function () {




				return new ViewService(
					$this->container->make( ViewEngineInterface::class ),
					$this->container->make( ViewComposerCollection::class ),
					$this->container->make('composers.globals')

				);

			} );

			/** @todo Why do we need this instead of injecting it directly */
			$this->container->singleton( 'view.compose.closure', function () {

				return function ( ViewInterface $view ) {

					$view_service = $this->container->make( ViewServiceInterface::class );
					$view_service->compose( $view );

					return $view;

				};
			} );

			$this->container->singleton( ViewFinderInterface::class, function () {

				return new PhpViewFinder( $this->config['views'] ?? [] );


			} );

			$this->container->singleton( PhpViewEngine::class, function () {

				return new PhpViewEngine(
					$this->container->make('view.compose.closure'),
					$this->container->make(ViewFinderInterface::class),
				);

			} );

			$this->container->singleton( ViewEngineInterface::class, function () {

				return $this->container->make( PhpViewEngine::class );

			} );

			$this->container->singleton( ViewComposerCollection::class, function () {

				return new ViewComposerCollection(
					$this->container->make(ViewComposerFactory::class),
					$this->container->make(ViewFinderInterface::class),
				);

			} );

			$this->container->singleton( ViewComposerFactory::class, function ( $c ) {

				return new ViewComposerFactory(
					$this->config['composers'] ?? [],
					$this->container,
				);

			} );


		}

		public function bootstrap() : void {
			// Nothing to bootstrap.
		}

	}
