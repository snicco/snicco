<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Request;

    class FooBarMiddleware extends Middleware {

        /**
         * @var string
         */
        private $foo;
        /**
         * @var string
         */
        private $bar;

        public function __construct($foo = 'foo', $bar = 'bar')
        {
            $this->foo = $foo;
            $this->bar = $bar;
        }

        public function handle( Request $request,  $next ) {

			if ( isset( $request->body ) ) {

				$request->body .= $this->foo.$this->bar;

				return $next( $request );
			}

			$request->body = $this->foo.$this->bar;

			return $next( $request );


		}

	}