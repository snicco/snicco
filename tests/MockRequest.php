<?php


	namespace Tests;

	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Events\IncomingWebRequest;

	trait MockRequest {


		public function createMockWebRequest() {

			$this->request->shouldReceive( 'getMethod' )->andReturn( 'GET' );
			$this->request->shouldReceive('setRoute')->andReturnUsing(function (RouteInterface $matched_route) {

				$this->request->test_route = $matched_route;

			});
			$this->request->test_type = IncomingWebRequest::class;
			$this->request->shouldReceive('type')->andReturn($this->request->test_type);

		}

	}