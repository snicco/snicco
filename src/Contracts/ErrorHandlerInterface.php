<?php


	declare( strict_types = 1 );


	namespace Snicco\Contracts;


	use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;
    use Throwable;

    interface ErrorHandlerInterface {

		/**
		 * Register any necessary error, exception and shutdown handlers.
		 *
		 * @return void
		 */
		public function register();

		/**
		 * Unregister any registered error, exception and shutdown handlers.
		 *
		 * @return void
		 */
		public function unregister();

        /**
         * Get a response representing the specified exception if possible.
         * If outside of the routing flow send error message and abort.
         *
         * @param  Throwable  $exception
         * @param  Request  $request
         *
         * @return Response|null
         */
		public function transformToResponse( Throwable $exception, Request $request) :?Response;

        public function unrecoverable ( Throwable $exception );

	}
