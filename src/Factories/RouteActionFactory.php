<?php


	declare( strict_types = 1 );


	namespace Snicco\Factories;

	use Closure;
	use Illuminate\Support\Reflector;
	use Snicco\Contracts\Handler;
	use Snicco\Contracts\RouteAction;
	use Snicco\Routing\ClosureAction;
	use Snicco\Routing\ControllerAction;
	use Snicco\Http\MiddlewareResolver;

	class RouteActionFactory extends AbstractFactory {

		public function create( $raw_handler, $route_namespace) {

			if ( $this->isClosure($raw_handler)) {

				return $this->createUsing($raw_handler);

			}

			if ( ! Reflector::isCallable($raw_handler) && ! empty($route_namespace) ) {

				return $this->createUsing(
					$route_namespace . '\\' . $raw_handler
				);

			}

			return $this->createUsing($raw_handler);


		}

		/**
		 * @param  string|array|callable  $raw_handler
		 *
		 * @return RouteAction
		 * @throws \Exception
		 */
		public function createUsing( $raw_handler ) : Handler {

			$handler = $this->normalizeInput( $raw_handler );

			if ( $handler[0] instanceof Closure ) {

				return new ClosureAction( $handler[0], $this->wrapClosure( $handler[0] ) );

			}

			if ( $namespaced_handler = $this->checkIsCallable( $handler ) ) {

				return new ControllerAction(
					$namespaced_handler,
					new MiddlewareResolver($this->container),
                    $this->container
				);

			}

			$this->fail( $handler[0], $handler[1] );

		}


	}