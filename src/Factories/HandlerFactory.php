<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Factories;

	use Closure;
	use Illuminate\Support\Reflector;
	use WPEmerge\Factories\AbstractFactory;
	use WPEmerge\Contracts\Handler;
	use WPEmerge\Contracts\RouteAction;
	use WPEmerge\Handlers\ClosureAction;
	use WPEmerge\Handlers\ControllerAction;
	use WPEmerge\Http\MiddlewareResolver;
	use WPEmerge\Traits\ReflectsCallable;

	class HandlerFactory extends AbstractFactory {


		public function create($raw_handler, $route_namespace) {

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
		 * @return \WPEmerge\Contracts\RouteAction
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
					$this->wrapClass( $namespaced_handler ),
					new MiddlewareResolver($this->container)
				);

			}

			$this->fail( $handler[0], $handler[1] );

		}


	}