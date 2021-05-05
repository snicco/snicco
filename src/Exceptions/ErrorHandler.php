<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


	namespace WPEmerge\Exceptions;

	use Psr\Http\Message\ResponseInterface as Psr7Response;
	use Throwable;
	use Whoops\RunInterface as Whoops;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface as Psr7Request;
	use WPEmerge\Contracts\ResponseServiceInterface as ResponseService;
	use WPEmerge\Responses\RedirectResponse;
	use WPEmerge\Support\Arr;
	use WPEmerge\Helpers\Url;

	class ErrorHandler implements ErrorHandlerInterface {


		private $response_service;

		private $whoops;

		private $debug;

		public function __construct( ResponseService $response_service, ?Whoops $whoops, $debug = false ) {

			$this->response_service = $response_service;
			$this->whoops           = $whoops;
			$this->debug            = $debug;

		}

		public function register() {

			if ( $this->whoops !== null ) {
				$this->whoops->register();
			}
		}

		public function unregister() {

			if ( $this->whoops !== null ) {
				$this->whoops->unregister();
			}
		}

		public function transformToResponse( Psr7Request $request, Throwable $exception ) : Psr7Response {

			if ( $response = $this->toResponse( $request, $exception ) ) {

				return $response;

			}

			if ( ! defined( 'TESTS_DIR' ) ) {
				// Only log errors if we are not running the test suite.
				error_log( $exception );

			}

			if ( $this->debug ) {

				return $this->toDebugResponse( $request, $exception );

			}

			return $this->response_service->abort( 500 );


		}

		private function toResponse(  Psr7Request $request , Throwable $exception ) {

			if ( $exception instanceof InvalidCsrfTokenException ) {
				wp_nonce_ays( '' );
			}

			if ( $exception instanceof NotFoundException ) {

				return $this->response_service->abort( 404 );

			}

			if ( $exception instanceof AuthorizationException ) {

				$url  = $exception->redirect_to ?? $request->getHeaderLine( 'Referer' );;

				return ( new RedirectResponse($request))->back( Url::addTrailing($url) );

			}

		}

		private function toDebugResponse( Psr7Request $request, Throwable $exception ) : Psr7Response {

			if ( $request->isAjax() ) {

				return $this->createAjaxDebugResponse( $exception );

			}

			if ( $this->whoops ) {

				return $this->toWhoopsResponse( $exception );

			}

			throw $exception;


		}

		private function createAjaxDebugResponse( Throwable $e ) : Psr7Response {

			$trace = collect( $e->getTrace() )->map( function ( $trace ) {

				return Arr::except( $trace, 'args' );

			} );

			$response = $this->response_service->json( [

				'message'   => $e->getMessage(),
				'exception' => get_class( $e ),
				'file'      => $e->getFile(),
				'line'      => $e->getLine(),
				'trace'     => $trace->all(),

			] );

			return $response->withStatus( 500 );

		}

		private function toWhoopsResponse( Throwable $exception ) : Psr7Response {

			$method = Whoops::EXCEPTION_HANDLER;
			ob_start();
			$this->whoops->$method( $exception );
			$response = ob_get_clean();

			return $this->response_service->output( $response )
			                              ->withStatus( 500 );

		}


	}
