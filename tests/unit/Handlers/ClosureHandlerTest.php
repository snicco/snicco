<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Handlers;

	use Mockery as m;
    use Tests\helpers\CreateContainer;
    use Tests\fixtures\TestDependencies\Bar;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Psr7\Request;

    class ClosureHandlerTest extends TestCase {

        use CreateContainer;

		/** @test */
		public function a_closure_handler_resolved_from_the_container_with_passed_parameter() {

			$container = $this->createContainer();

			$factory = new RouteActionFactory( [], $container);

			$request = m::mock( Request::class);

			$request->foo = 'foo_route';

			$raw_closure = function ( Request $request, string $url , Bar $bar ) {

					return $request->foo . '_' . $url . '_' . $bar->bar;

			};

			$handler = $factory->createUsing($raw_closure);

			$result = $handler->executeUsing(['request' => $request, 'url' => 'url']);

			$this->assertSame('foo_route_url_bar', $result);

		}

	}
