<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http;

	class Controller {


		/**
		 * Middleware.
		 *
		 * @var \WPEmerge\Http\ControllerMiddleware[]
		 */
		private $middleware = [];


		public function getMiddleware( string $method = null ) : array {

			return collect( $this->middleware )
				->filter( function ( ControllerMiddleware $middleware ) use ( $method ) {

					return $middleware->appliesTo( $method );

				} )
				->map( function ( ControllerMiddleware $middleware ) {

					return $middleware->name();

				} )
				->values()
				->all();


		}

		protected function middleware( string $middleware_name ) : ControllerMiddleware {


			return $this->middleware[] = new ControllerMiddleware( $middleware_name );


		}


	}