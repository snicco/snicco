<?php


	namespace WPEmerge\Exceptions;

	use GuzzleHttp\Psr7\Response;
	use GuzzleHttp\Psr7\Utils;
	use Psr\Http\Message\ResponseInterface;
	use Throwable;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;

	class ProductionErrorHandler implements ErrorHandlerInterface {

		public function register() {

			//

		}

		public function unregister() {

			//

		}

		public function transformToResponse( RequestInterface $request, Throwable $exception ) : ResponseInterface {

			return $this->defaultResponse($request);


		}

		private function contentType(RequestInterface $request) {

			return ( $request->isAjax() ) ? 'application/json' : 'text/html';

		}

		private function body( $body, RequestInterface $request ) {

			return ( $request->expectsJson() )
				? Utils::streamFor(json_encode($body))
				: Utils::streamFor($body);

		}

		protected function defaultResponse(RequestInterface $request) : Response {

			return new Response(
				500,
				['Content-Type' => $this->contentType($request)],
				'Internal Server Error'
			);

		}

	}