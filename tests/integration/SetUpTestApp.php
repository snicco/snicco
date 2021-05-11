<?php


	declare( strict_types = 1 );


	namespace Tests\integration;

	use Mockery as m;
	use Psr\Http\Message\ResponseInterface;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\TestErrorHandler;
	use Tests\stubs\TestApp;
	use Tests\stubs\TestResponseService;
	use WPEmerge\Http\Request;

	trait SetUpTestApp {


		abstract public function config() : array;

		private function bootstrapTestApp() {

			TestApp::make( new BaseContainerAdapter() )->boot( $this->config() );
			TestApp::container()[ WPEMERGE_REQUEST_KEY ]                  = $this->request;
			TestApp::container()[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = new TestErrorHandler();
			TestApp::container()[ WPEMERGE_RESPONSE_SERVICE_KEY ]         = $this->response_service ?? new TestResponseService();


		}

		private function assertResponseSend() {

			$this->assertSame(
				$this->response_service->header_response,
				$this->response_service->body_response
			);

			$this->assertInstanceOf( ResponseInterface::class, $this->response_service->header_response );


		}

		private function responseBody() {

			$response = $this->response_service->body_response;

			return $response->body();

		}

	}