<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Admin;

	use Tests\stubs\Middleware\MiddlewareWithDependencies;
	use Tests\stubs\TestResponse;
	use Tests\TestRequest;
	use WPEmerge\Http\Controller;
	use Tests\stubs\Baz;
	use WPEmerge\Http\Response;

	class AdminControllerWithMiddleware extends Controller {

		/**
		 * @var \Tests\stubs\Baz
		 */
		private $baz;

		const constructed_times = 'controller_with_middleware';

		public function __construct( Baz $baz ) {

			$this->middleware(MiddlewareWithDependencies::class);

			$this->baz = $baz;

			$count = $GLOBALS['test'][ self::constructed_times ] ?? 0;
			$count ++;
			$GLOBALS['test'][ self::constructed_times ] = $count;

		}

		public function handle( TestRequest $request ) : Response {

			$request->body .= $this->baz . ':controller_with_middleware';

			return new Response($request->body);

		}


	}

