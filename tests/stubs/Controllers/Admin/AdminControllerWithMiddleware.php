<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Admin;

    use Tests\stubs\Middleware\MiddlewareWithDependencies;
	use WPEmerge\Http\Controller;
	use Tests\stubs\Baz;
	use WPEmerge\Http\Psr7\Response;
	use WPEmerge\Http\Psr7\Request;

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

		public function handle( Request $request ) : string
        {

			$request->body .= $this->baz->baz . ':controller_with_middleware';

			return $request->body;

		}


	}

