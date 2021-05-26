<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Middleware;

	use Tests\fixtures\TestDependencies\Bar;
    use Tests\fixtures\TestDependencies\Baz;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Psr7\Request;

    class BazMiddleware extends Middleware {

        /**
         * @var Baz
         */
        private $baz;

        public function __construct( $baz = 'baz')
        {
            $this->baz = $baz;
        }

        public function handle( Request $request, $next ) {

			if ( isset( $request->body ) ) {

				$request->body .= $this->baz;

				return $next( $request );
			}

			$request->body = $this->baz;

			return $next( $request );

		}

	}