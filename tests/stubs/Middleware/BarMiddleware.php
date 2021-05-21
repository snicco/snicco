<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

	use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Request;

    class BarMiddleware extends Middleware {

        /**
         * @var string
         */
        private $bar;

        public function __construct( $bar = 'bar')
        {

            $this->bar = $bar;
        }

        public function handle( Request $request, $next) {

			if ( isset( $request->body ) ) {

				$request->body .= $this->bar;

				return $next( $request );
			}

			$request->body = $this->bar;

			return $next( $request );

		}

	}