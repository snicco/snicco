<?php


	namespace WPEmerge\Exceptions;

	use GuzzleHttp\Psr7\Response;
	use GuzzleHttp\Psr7\Utils;
	use Psr\Http\Message\ResponseInterface;
	use Throwable;
	use Whoops\RunInterface;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;

	class DebugErrorHandler implements ErrorHandlerInterface {


		/** @var \Whoops\RunInterface */
		private $whoops;

		/** @var bool */
		private $is_ajax;


		public function __construct( RunInterface $whoops, $is_ajax = false ) {

			$this->whoops = $whoops;

			$this->is_ajax = $is_ajax;
		}


		public function register() {

			$this->whoops->register();

		}

		public function unregister() {

			$this->whoops->unregister();

		}

		public function transformToResponse( RequestInterface $request, Throwable $exception ) : ResponseInterface {

			$method = RunInterface::EXCEPTION_HANDLER;

			$output = $this->whoops->{$method}( $exception );

			$content_type = ( $this->is_ajax ) ? 'application/json' : 'text/html';

			return ( new Response() )
				->withHeader( 'Content-Type', $content_type )
				->withBody( Utils::streamFor( $output ) )
				->withStatus( 500 );



		}

	}