<?php


	namespace Tests\stubs\Controllers\Admin;

	use Tests\stubs\Middleware\MiddlewareWithDependencies;
	use Tests\stubs\TestResponse;
	use Tests\TestRequest;
	use WPEmerge\Http\Controller;
	use Tests\stubs\Baz;

	class AdminControllerWithMiddleware extends Controller {

		/**
		 * @var \Tests\stubs\Baz
		 */
		private $baz;

		public function __construct( Baz $baz ) {

			$this->middleware(MiddlewareWithDependencies::class);

			$this->baz = $baz;

		}

		public function handle( TestRequest $request ) : TestResponse {

			$request->body .= $this->baz . ':controller_with_middleware';

			return new TestResponse($request);

		}


	}

