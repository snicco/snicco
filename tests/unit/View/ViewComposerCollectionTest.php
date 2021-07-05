<?php


	declare( strict_types = 1 );


	namespace Tests\unit\View;

	use PHPUnit\Framework\TestCase;
    use Tests\stubs\TestView;
    use Tests\helpers\CreateContainer;
    use BetterWP\Contracts\ViewInterface;
	use BetterWP\View\PhpViewFinder;
	use BetterWP\View\ViewComposerCollection;
	use BetterWP\Factories\ViewComposerFactory;

    use const DS;
    use const TEST_CONFIG;

    class ViewComposerCollectionTest extends TestCase {


	    use CreateContainer;

		/**
		 * @var ViewComposerFactory
		 */
		private $factory;

		protected function setUp() : void {

			parent::setUp();

			$this->factory = new ViewComposerFactory(
				TEST_CONFIG['composers'],
				$this->createContainer()
			);


		}

		/** @test */
		public function a_view_can_be_composed_if_it_has_a_matching_composer() {

			$collection = $this->newViewComposerCollection();

			$view = new TestView('foo_view');
			$view->with( [ 'foo' => 'bar' ] );

			$collection->addComposer( 'foo_view', function ( ViewInterface $view ) {

				$view->with( [ 'foo' => 'baz' ] );

			} );

			$collection->executeUsing( $view );

			$this->assertSame( 'baz', $view->context( 'foo' ) );

		}

		/** @test */
		public function the_view_is_not_changed_if_no_composer_matches() {

			$collection = $this->newViewComposerCollection();

			$view = new TestView('foo_view');
			$view->with( [ 'foo' => 'bar' ] );

			$collection->addComposer( 'bar_view', function ( ViewInterface $view ) {

				$view->with( [ 'foo' => 'baz' ] );

			} );

			$collection->executeUsing( $view );

			$this->assertSame( 'bar', $view->context( 'foo' ) );

		}

		/** @test */
		public function multiple_composers_can_match_for_one_view() {

			$collection = $this->newViewComposerCollection();

			$view = new TestView('foo_view');

			$collection->addComposer( 'foo_view', function ( ViewInterface $view ) {

				$view->with( [ 'foo' => 'bar' ] );

			} );

			$collection->addComposer( 'foo_view', function ( ViewInterface $view ) {

				$view->with( [ 'bar' => 'baz' ] );

			} );

			$collection->executeUsing( $view );

			$this->assertSame( 'bar', $view->context( 'foo' ) );
			$this->assertSame( 'baz', $view->context( 'bar' ) );

		}

		/** @test */
		public function a_composer_can_be_created_for_multiple_views () {

			$collection = $this->newViewComposerCollection();

			$collection->addComposer( ['view_one', 'view_two'], function ( ViewInterface $view ) {

				$view->with( [ 'foo' => 'bar' ] );

			} );

			$view1 = new TestView('view_one');

			$collection->executeUsing( $view1 );
			$this->assertSame( 'bar', $view1->context( 'foo' ) );

			$view2 = new TestView('view_two');
			$collection->executeUsing( $view2 );
			$this->assertSame( 'bar', $view2->context( 'foo' ) );



		}

		/** @test */
		public function the_view_does_not_need_to_be_type_hinted () {

			$collection = $this->newViewComposerCollection();

			$view = new TestView('foo_view');
			$view->with( [ 'foo' => 'bar' ] );

			$collection->addComposer( 'foo_view', function ( $view_file ) {

				$view_file->with( [ 'foo' => 'baz' ] );

			} );

			$collection->executeUsing( $view );

			$this->assertSame( 'baz', $view->context( 'foo' ) );


		}

		private function newViewComposerCollection() : ViewComposerCollection {

			$dir = getenv( 'ROOT_DIR' ) . DS . 'tests' . DS . 'views';

			return new ViewComposerCollection( $this->factory );

		}


	}


