<?php


	declare( strict_types = 1 );


	namespace Tests;

    use WPEmerge\Http\Response;

    trait AssertsResponse {


		private function assertStatusCode(int $code, Response $response) {

			$this->assertSame($code , $response->getStatusCode());

		}

		private function assertContentType( string $type, Response $response ) {

			$this->assertSame($type, $response->getHeaderLine('Content-Type'));

		}

		private function assertOutput ( $output , Response $response ) {

			$this->assertStringContainsString($output, $response->getBody()->read(999));

		}

		private function assertHeader ( $name, $value , Response $response) {

			$this->assertSame($value, $response->getHeaderLine($name));

		}

	}