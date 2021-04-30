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


				$r->addRoute( 'GET', '/users', 'get_all_users_handler' );
				// {id} must be a number (\d+)
				$r->addRoute( 'GET', '/user/{id:\d+}', 'get_user_handler' );
				// The /{title} suffix is optional
				$r->addRoute( 'GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler' );

				$r->addRoute('POST', '/users', 'foobar');


			} );


			$routeInfo = $dispatcher->dispatch( 'GET', '/articles/12/foobar' );

			switch ( $routeInfo[0] ) {
				case Dispatcher::NOT_FOUND:
					// ... 404 Not Found
					break;
				case Dispatcher::METHOD_NOT_ALLOWED:
					$allowedMethods = $routeInfo[1];
					// ... 405 Method Not Allowed
					break;
				case Dispatcher::FOUND:
					$handler = $routeInfo[1];
					$vars    = $routeInfo[2];


			}

		}

	}