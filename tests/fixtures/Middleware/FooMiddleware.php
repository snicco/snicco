<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Middleware;

	use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Http\Psr7\Request;

    class FooMiddleware extends Middleware {

        /**
         * @var mixed|string
         */
        private $foo;

        public function __construct( $foo = 'foo')
        {
            $this->foo = $foo;
        }

        public function handle( Request $request, $next ) :ResponseInterface{

			if ( isset( $request->body ) ) {

				$request->body .= $this->foo;

				return $next( $request );
			}

			$request->body = $this->foo;

			return $next( $request );


		}

	}