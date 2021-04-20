<?php


	namespace Tests\unit\Handlers;

	use Mockery as m;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\Bar;
	use WPEmerge\Contracts\RequestInterface as Request;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Handlers\HandlerFactory;

	class ClosureHandlerTest extends TestCase {



		/** @test */
		public function a_closure_handler_resolved_from_the_container_with_passed_parameter() {

			$container = new BaseContainerAdapter();

			$factory = new HandlerFactory( [], $container);

			$request = m::mock(\WPEmerge\Requests\Request::class);

			$request->foo = 'foo_route';

			$raw_closure = function ( Request $request, string $url , Bar $bar ) {

					return $request->foo . '_' . $url . '_' . $bar->bar;

			};

			$handler = $factory->createRouteHandlerUsing($raw_closure);

			$result = $handler->executeUsing(['request' => $request, 'url' => 'url']);

			$this->assertSame('foo_route_url_bar', $result);

		}

	}
