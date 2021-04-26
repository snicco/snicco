<?php


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Session\OldInputStore as OldInputStore;

	class OldInput implements Middleware {

		/** @var \WPEmerge\Session\OldInputStore */
		protected $old_input;


		public function __construct( OldInputStore $old_input ) {

			$this->old_input = $old_input;
		}


		public function handle( RequestInterface $request, Closure $next ) {

			if ( $this->old_input->enabled() && $request->isPost() ) {
				$this->old_input->set( $request->body() );
			}

			return $next( $request );

		}

	}
