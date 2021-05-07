<?php
	/**
	 * @package   WPEmerge
	 * @author    Atanas Angelov <hi@atanas.dev>
	 * @copyright 2017-2019 Atanas Angelov
	 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
	 * @link      https://wpemerge.com/
	 */


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\Support\Path;
	use WPEmerge\Traits\ExtendsConfig;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\View\PhpViewFinder;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\View\ViewService;

	use WPEmerge\ViewComposers\ViewComposerCollection;
	use WPEmerge\Factories\ViewComposerFactory;

	use function get_stylesheet_directory;
	use function get_template_directory;

	use const WPEMERGE_CONFIG_KEY;
	use const WPEMERGE_VIEW_COMPOSE_ACTION_KEY;
	use const WPEMERGE_VIEW_ENGINE_KEY;
	use const WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY;
	use const WPEMERGE_VIEW_SERVICE_KEY;

	/**
	 * Provide view dependencies
	 *
	 */
	class ViewServiceProvider implements ServiceProviderInterface {

		use ExtendsConfig;

		public function register( $container ) {

			/** @todo Refactor to custom class without wp functions */
			$this->extendConfig( $container, 'views', [
				get_stylesheet_directory(),
				get_template_directory(),
			] );

			$this->extendConfig( $container, 'view_composers', [
				'namespace' => 'App\\ViewComposers\\',
			] );

			$container->singleton( ViewServiceInterface::class, function ( $c ) {

				return new ViewService(
					$c[ WPEMERGE_VIEW_ENGINE_KEY ],
					$c[ViewComposerCollection::class],
					$c['global.variables']

				);
			} );

			$container->singleton( WPEMERGE_VIEW_COMPOSE_ACTION_KEY, function ( $c ) {

				return function ( ViewInterface $view ) use ( $c ) {

					$view_service = $c[ WPEMERGE_VIEW_SERVICE_KEY ];
					$view_service->compose( $view );

					return $view;
				};
			} );

			$container->singleton(ViewFinderInterface::class, function ($c) {

				return new PhpViewFinder(
					$c[ WPEMERGE_CONFIG_KEY ]['views'] ?? []
				);

			});

			$container->singleton( WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY, function ( $c ) {

				return new PhpViewEngine(
					$c[ WPEMERGE_VIEW_COMPOSE_ACTION_KEY ],
					$c[ViewFinderInterface::class]
				);

			});

			$container->singleton( WPEMERGE_VIEW_ENGINE_KEY, function ( $c ) {

				return $c[ WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY ];

			} );

			$container->singleton( ViewComposerCollection::class, function ( $c ) {

				return new ViewComposerCollection(
					$c[ViewComposerFactory::class],
					$c[ViewFinderInterface::class]
				);

			} );

			$container->singleton(ViewComposerFactory::class, function ($c) {

				return new ViewComposerFactory(
					$c[WPEMERGE_CONFIG_KEY]['composers'] ?? [],
					$c[WPEMERGE_CONTAINER_ADAPTER]
			);

			});

			$container->singleton('global.variables', function ($container) {

				return new VariableBag();

			});

		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}
