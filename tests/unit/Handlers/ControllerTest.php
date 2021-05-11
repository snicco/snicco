<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Handlers;

	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\Bar;
	use Tests\stubs\Foo;
	use WPEmerge\Contracts\RequestInterface as Request;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Factories\HandlerFactory;
	use Mockery as m;

	class ControllerTest extends TestCase {

		/** @test */
		public function a_controller_gets_resolved_from_the_container_with_passed_parameter() {

			$container = new BaseContainerAdapter();

			$factory = new HandlerFactory( [], $container);

			$request = m::mock( \WPEmerge\Http\Request::class);

			$request->foo = 'foo_route';

			$controller = $factory->createUsing([TestController::class, 'foo']);

			$result = $controller->executeUsing(['request' => $request, 'url' => 'url']);

			$this->assertSame('foo_route_url_foo_bar', $result);

		}

	}

	class TestController {


		/**
		 * @var \Tests\stubs\Bar
		 */
		private $bar;

		public function __construct( Bar $bar) {

			$this->bar = '_' . $bar->bar;
		}

		public function foo (Request $request, string $url, Foo $foo ) {

			return $request->foo . '_' . $url . '_' . $foo->foo . $this->bar;

		}

	}


