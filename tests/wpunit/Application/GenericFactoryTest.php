<?php


	namespace Tests\wpunit\Application;

	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Exceptions\ClassNotFoundException;
	use Tests\stubs\TestService;

	/**
	 * @coversDefaultClass \WPEmerge\Application\GenericFactory
	 */
	class GenericFactoryTest extends TestCase {


		/**
		 * @var GenericFactory
		 */
		private $subject;

		/**
		 * @var BaseContainerAdapter
		 */
		private $container;

		public function setUp() : void {

			parent::setUp();

			$this->container = new BaseContainerAdapter();
			$this->subject   = new GenericFactory( $this->container );

		}

		public function tearDown() : void {

			parent::tearDown();

			unset( $this->subject );
			unset( $this->container );

		}

		/**
		 * @test
		 * @covers ::make
		 */
		public function a_class_not_in_the_service_container_is_always_a_fresh_instance() {

			$class = TestService::class;

			$instance1 = $this->subject->make( $class );
			$instance2 = $this->subject->make( $class );

			$this->assertInstanceOf( $class, $instance1 );
			$this->assertInstanceOf( $class, $instance2 );
			$this->assertNotSame( $instance1, $instance2 );
		}

		/**
		 * @test
		 * @covers ::make
		 */
		public function non_existing_classes_raise_an_exception () {

			$this->expectException(ClassNotFoundException::class);
			$this->expectExceptionMessage('Class not found');

			$class = 'Foobar class';

			$this->subject->make( $class );
		}

		/**
		 * @test
		 * @covers ::make
		 */
		public function known_classes_are_resolved_from_the_service_container() {

			$expected = 'foo';
			$class    = TestService::class;

			$this->container[TestService::class] = function () {

				$test = new TestService();
				$test->setTest('foo');

				return $test;

			};

			$instance = $this->subject->make( $class );

			$this->assertEquals( $expected, $instance->getTest() );
		}

	}
