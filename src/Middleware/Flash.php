<?php



	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Session\FlashStore;

	class Flash implements Middleware {

		/** @var \WPEmerge\Session\FlashStore  */
		private $flash;

		public function __construct( FlashStore $flash ) {

			$this->flash = $flash;

		}

		public function handle( RequestInterface $request, Closure $next ) {

			$response = $next( $request );

			if ( $this->flash->enabled() ) {

				$this->flash->shift();
				$this->flash->save();

			}

			return $response;
		}

	}
