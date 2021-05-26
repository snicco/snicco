<?php


	declare( strict_types = 1 );


	namespace Tests\helpers;

    use PHPUnit\Framework\Assert;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Http\Psr7\Response;

    trait AssertsResponse {


		private function assertStatusCode(int $code, Response $response) {

			$this->assertSame($code , $response->getStatusCode());

		}

		private function assertContentType( string $type, Response $response ) {

			$this->assertSame($type, $response->getHeaderLine('Content-Type'));

		}

        private function assertOutput ( $output , Response $response ) {

            $this->assertSame($output, $response->getBody()->__toString());

        }

		private function assertOutputContains ( $output , Response $response ) {

			$this->assertStringContainsString($output, $response->getBody()->__toString());

		}

		private function assertNullResponse( Response $response ) {

		    Assert::assertInstanceOf(NullResponse::class, $response);

        }

		private function assertHeader ( $name, $value , Response $response) {

			$this->assertSame($value, $response->getHeaderLine($name));

		}

	}