<?php


	namespace Tests\stubs;

	use GuzzleHttp\Psr7\Response;
	use GuzzleHttp\Psr7\Utils;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;
	use WPEmerge\Http\RedirectResponse;

	class TestResponseService implements ResponseServiceInterface {

		/** @var \Tests\stubs\TestResponse */
		public $body_response;

		/** @var \Tests\stubs\TestResponse */
		public $header_response;



		public function sendBody( ResponseInterface $response, int $chunk_size = 4096 ) {

			$this->body_response = $response;

		}

		public function sendHeaders( ResponseInterface $response ) :void {

			$this->header_response = $response;

		}

		public function respond( ResponseInterface $response ) : void {
			// Nothing
		}

		public function output( string $output ) :ResponseInterface{

			$response = new Response();
			return $response->withBody( Utils::streamFor( $output ) );

		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		public function json( $data ) :ResponseInterface {

			return ( new Response() )
				->withHeader( 'Content-Type', 'application/json' )
				->withBody( Utils::streamFor( json_encode( $data ) ) );

		}

		public function redirect( ?RequestInterface $request ) :RedirectResponse{
			// Nothing
		}

		public function abort( int $status_code ) : ResponseInterface {

			return (new Response())->withStatus($status_code);

		}

	}