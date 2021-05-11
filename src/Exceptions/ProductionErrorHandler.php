<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Exceptions;

	use Contracts\ContainerAdapter;
	use Psr\Log\LoggerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;
	use Throwable;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\Http\Response;
	use WPEmerge\Support\Arr;
	use WPEmerge\Traits\HandlesExceptions;

	class ProductionErrorHandler implements ErrorHandlerInterface {

		use HandlesExceptions;

		/**
		 * @var bool
		 */
		private $is_ajax;

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		/**
		 * @var \Psr\Log\LoggerInterface
		 */
		private $logger;

		/**
		 * @var array
		 */
		protected $dont_report = [];

		public function __construct( ContainerAdapter $container, LoggerInterface $logger, bool $is_ajax ) {

			$this->is_ajax   = $is_ajax;
			$this->container = $container;
			$this->logger    = $logger;

		}

		public function handleException( $exception, $in_routing_flow = false, RequestInterface $request = null ) {


			$request = $request ?? $this->container->make( RequestInterface::class );

			$this->logException( $exception, $request );

			$response = $this->createResponseObject( $exception, $request );

			if ( $in_routing_flow ) {

				return $response;

			}

			$this->sendToClient( $response, $request );

			// Shuts down the script
			UnrecoverableExceptionHandled::dispatch();

		}

		public function transformToResponse( Throwable $exception, RequestInterface $request = null ) : ResponseInterface {

			return $this->handleException( $exception, true, $request );

		}

		/**
		 *
		 * Override this method from a child class to create
		 * your own globalContext.
		 *
		 * @return array
		 */
		protected function globalContext() : array {

			try {
				return array_filter( [
					'user_id' => get_current_user_id(),
				] );
			}
			catch ( Throwable $e ) {
				return [];
			}

		}

		private function contentType() : string {

			return ( $this->is_ajax ) ? 'application/json' : 'text/html';

		}

		private function defaultResponse() : ResponseInterface {

			return ( new Response( 'Internal Server Error', 500 ) )
				->setType( $this->contentType() );

		}

		private function createResponseObject( Throwable $e, RequestInterface $request ) : ResponseInterface {


			if ( method_exists( $e, 'render' ) ) {

				/** @var ResponseInterface $response */
				$response = $this->container->call( [ $e, 'render' ], [ 'request' => $request ] );

				return $response->setType( $this->contentType() );

			}

			return $this->defaultResponse();

		}

		private function logException( Throwable $exception, $request ) {

			if ( in_array(get_class($exception), $this->dont_report) ) {

				return;

			}

			if ( method_exists( $exception, 'report' ) ) {

				if ( $this->container->call( [ $exception, 'report' ] ) === false ) {

					return;

				}

			}

			$this->logger->error(
				$exception->getMessage(),
				array_merge(
					$this->globalContext(),
					$this->exceptionContext( $exception ),
					[ 'exception' => $exception ]
				)
			);

		}

		private function exceptionContext( Throwable $e ) {

			if ( method_exists( $e, 'context' ) ) {
				return $e->context();
			}

			return [];
		}

		private function sendToClient( ResponseInterface $response, RequestInterface $request ) {

			$response->prepareForSending( $request );
			$response->sendHeaders();
			$response->sendBody();
		}


	}