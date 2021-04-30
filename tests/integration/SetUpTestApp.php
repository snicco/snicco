<?php


	namespace Tests\integration;

	use Mockery as m;
	use Psr\Http\Message\ResponseInterface;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\IntegrationTestErrorHandler;
	use Tests\stubs\TestApp;
	use Tests\stubs\TestResponseService;
	use WPEmerge\Requests\Request;

	trait SetUpTestApp {


		abstract public function config() : array;

		private function bootstrapTestApp() {

			TestApp::make( new BaseContainerAdapter() )->bootstrap( $this->config() );
			TestApp::container()[ WPEMERGE_REQUEST_KEY ]                  = $this->request;
			TestApp::container()[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = new IntegrationTestErrorHandler();
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