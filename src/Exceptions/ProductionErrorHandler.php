<?php


	namespace WPEmerge\Exceptions;

	use WPEmerge\Contracts\ResponseInterface;
	use Throwable;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Http\Response;

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

		private function contentType(RequestInterface $request) : string {

			return ( $request->isAjax() ) ? 'application/json' : 'text/html';

		}



		protected function defaultResponse(RequestInterface $request) : Response {

			return (new Response( 'Internal Server Error', 500))
				->setType($this->contentType($request));

		}

	}