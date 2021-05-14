<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Exceptions\AuthorizationException;
	use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;

    class Authorize extends Middleware {

        /**
         * @var string
         */
        private $capability;

        /** @var array  */
        private $args;

        public function __construct(string $capability, ...$args)
        {

            $this->capability = $capability;
            $this->args = $args;

        }

        public function handle( Request $request, $next ) {


			if ( WP::currentUserCan($this->capability, ...$this->args ) ) {

				return $next( $request );

			}

			throw new AuthorizationException('You do not have permission to perform this action');

		}

	}
