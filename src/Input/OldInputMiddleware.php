<?php


	namespace WPEmerge\Input;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;


	class OldInputMiddleware implements Middleware {

		/** @var \WPEmerge\Input\OldInput */
		protected $old_input;


		public function __construct( OldInput $old_input ) {

			$this->old_input = $old_input;
		}


		public function handle( RequestInterface $request, Closure $next ) {

			if ( $this->old_input->enabled() && $request->isPost() ) {
				$this->old_input->set( $request->body() );
			}

			return $next( $request );

		}

	}
