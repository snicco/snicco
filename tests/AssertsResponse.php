<?php


	declare( strict_types = 1 );


	namespace Tests;

	use WPEmerge\Contracts\ResponseInterface;

	trait AssertsResponse {

		private function createRequest() : TestRequest {

			return TestRequest::from('GET', 'foo');


		}

		private function assertStatusCode(int $code, ResponseInterface $response) {

			$this->assertSame($code , $response->status());

		}

		private function assertContentType( string $type, ResponseInterface $response ) {

			$this->assertSame($type, $response->header('Content-Type'));

		}

		private function assertOutput ( $output , ResponseInterface $response ) {

			$this->assertStringContainsString($output, $response->body());

		}

		private function assertHeader ( $name, $value , ResponseInterface $response) {

			$this->assertSame($value, $response->header($name));

		}

	}