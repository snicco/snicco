<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ViewComposers;

	use Illuminate\Support\Collection;
	use WPEmerge\Contracts\ViewComposer;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Factories\ViewComposerFactory;

	class ViewComposerCollection implements ViewComposer {

		/**
		 * @var ViewComposer[]
		 */
		private $composers;

		/**
		 * @var \WPEmerge\Factories\ViewComposerFactory
		 */
		private $composer_factory;
		/**
		 * @var ViewFinderInterface
		 */
		private $view_finder;


		public function __construct( ViewComposerFactory $composer_factory, ViewFinderInterface $view_finder ) {

			$this->composers        = new Collection();
			$this->composer_factory = $composer_factory;
			$this->view_finder      = $view_finder;

		}

		public function executeUsing( ...$args ) {

			$view = $args[0];

			$composers = $this->matchingComposers( $view );

			array_walk( $composers, function ( ViewComposer $composer ) use ( $view ) {

				$composer->executeUsing( $view );

			} );

		}

		/**
		 * @param  string|string[]  $views
		 * @param  string|array|callable  $callable
		 *
		 * @throws \Exception
		 */
		public function addComposer( $views, $callable ) {

			$views = collect( $views )->map( function ( $view ) {

				return $this->view_finder->filePath( $view );

			} )->all();

			$this->composers->push( [

				'views'    => $views,
				'composer' => $this->composer_factory->createUsing( $callable ),

			] );

		}

		private function matchingComposers( ViewInterface $view ) {

			return $this->composers
				->filter( function ( $value ) use ( $view ) {

					return in_array( $this->view_finder->filePath( $view->getName() ), $value['views'] );

				})
				->pluck('composer')
				->all();

		}

	}