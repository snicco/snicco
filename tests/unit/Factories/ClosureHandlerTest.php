<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Factories;

	use Mockery as m;
    use PHPUnit\Framework\TestCase;
    use Snicco\Factories\RouteActionFactory;
    use Snicco\Http\Psr7\Request;
    use Tests\fixtures\TestDependencies\Bar;
    use Tests\helpers\CreateContainer;

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
