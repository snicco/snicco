<?php


	namespace Tests\unit\ViewComposers;

	use PHPUnit\Framework\TestCase;
	use Psr\Http\Message\ResponseInterface;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Support\Arr;
	use WPEmerge\View\PhpViewFilesystemFinder;
	use WPEmerge\ViewComposers\ViewComposerCollection;
	use WPEmerge\ViewComposers\ViewComposerFactory;

	class ViewComposerCollectionTest extends TestCase {


		/**
		 * @var \WPEmerge\ViewComposers\ViewComposerFactory
		 */
		private $factory;

		protected function setUp() : void {

			parent::setUp();

			$this->factory = new ViewComposerFactory(
				TEST_CONFIG['composers'],
				new BaseContainerAdapter()
			);


		}

		/** @test */
		public function a_view_can_be_composed_if_it_has_a_matching_composer() {

			$collection = $this->newViewComposerCollection();

			$view = new TestView();
			$view->setName( 'view.php' );
			$view->with( [ 'foo' => 'bar' ] );

			$collection->addComposer( 'view.php', function ( ViewInterface $view ) {

				$view->with( [ 'foo' => 'baz' ] );

			} );

			$collection->executeUsing( $view );

			$this->assertSame( 'baz', $view->getContext( 'foo' ) );

		}

		/** @test */
		public function the_view_is_not_changed_if_no_composer_matches() {

			$collection = $this->newViewComposerCollection();

			$view = new TestView();
			$view->setName( 'view.php' );
			$view->with( [ 'foo' => 'bar' ] );

			$collection->addComposer( 'fooview.php', function ( ViewInterface $view ) {

				$view->with( [ 'foo' => 'baz' ] );

			} );

			$collection->executeUsing( $view );

			$this->assertSame( 'bar', $view->getContext( 'foo' ) );

		}

		/** @test */
		public function multiple_composers_can_match() {

			$collection = $this->newViewComposerCollection();

			$view = new TestView();
			$view->setName( 'view.php' );

			$collection->addComposer( 'view.php', function ( ViewInterface $view ) {

				$view->with( [ 'foo' => 'bar' ] );

			} );

			$collection->addComposer( 'view.php', function ( ViewInterface $view ) {

				$view->with( [ 'bar' => 'baz' ] );

			} );

			$collection->executeUsing( $view );

			$this->assertSame( 'bar', $view->getContext( 'foo' ) );
			$this->assertSame( 'baz', $view->getContext( 'bar' ) );

		}

		private function newViewComposerCollection() : ViewComposerCollection {

			$dir = getenv( 'ROOT_DIR' ) . DS . 'tests' . DS . 'views';

			return new ViewComposerCollection( $this->factory, new PhpViewFilesystemFinder( [ $dir ] ) );

		}

	}


	class TestView implements ViewInterface {

		private $context = [];

		private $name;

		public function getContext( $key = null, $default = null ) {

			if ( $key === null ) {
				return $this->context;
			}

			return Arr::get( $this->context, $key, $default );

		}

		public function with( $key, $value = null ) {

			if ( is_array( $key ) ) {
				$this->context = array_merge( $this->getContext(), $key );
			} else {
				$this->context[ $key ] = $value;
			}

		}

		public function toResponse() {


		}

		public function getName() {

			return $this->name;

		}

		public function setName( $name ) {

			$this->name = $name;

		}

		public function toString() {

		}

	}