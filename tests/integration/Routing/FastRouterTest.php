<?php


	namespace Tests\integration\Routing;

	use FastRoute\RouteCollector;
	use FastRoute\Dispatcher;

	use PHPUnit\Framework\TestCase;

	use Tests\TestRequest;

	use function FastRoute\simpleDispatcher;

	class FastRouterTest extends TestCase {


		/** @test */
		public function playground() {

			$dispatcher = simpleDispatcher( function ( RouteCollector $r ) {

				// $r->addRoute( 'GET', '/users/{user}/', 'get_all_users_handler' );
				// $r->addRoute( 'GET', '/users/{foo}/', 'get_all_users_handler' );

				$r->addRoute('GET', '/user/{name:.+}', 'handler');

				$r->addRoute('GET', '/team/{id:\d+}[/{name}/{player}]', 'handler');

			} );

			$routeInfo = $dispatcher->dispatch( 'GET', '/team/10/dortmund/calvin' );

			switch ( $routeInfo[0] ) {
				case Dispatcher::NOT_FOUND:
					$this->fail('404 Not Found');
				case Dispatcher::METHOD_NOT_ALLOWED:
					$allowedMethods = $routeInfo[1];
					$this->fail('405 Method not allowed');
					break;
				case Dispatcher::FOUND:
					$handler = $routeInfo[1];
					$vars    = $routeInfo[2];

					$this->assertTrue(true);

			}

		}

	}