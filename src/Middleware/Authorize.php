<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Exceptions\AuthorizationException;
	use WPEmerge\Facade\WP;

	class Authorize implements Middleware {


		public function handle( RequestInterface $request, Closure $next, string $capability, ...$args ) {


			if ( WP::currentUserCan($capability, ...$args ) ) {

				return $next( $request );

			}

			throw new AuthorizationException('You do not have permission to perform this action');

		}

	}
