<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Middleware;

	use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Psr7\Request;

    class BazMiddleware extends Middleware {

        private string $baz;

        public function __construct( $baz = 'baz')
        {
            $this->baz = $baz;
        }

        public function handle( Request $request, $next ) :ResponseInterface {

			if ( isset( $request->body ) ) {

				$request->body .= $this->baz;

				return $next( $request );
			}

			$request->body = $this->baz;

			return $next( $request );

		}

	}