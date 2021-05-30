<?php


	declare( strict_types = 1 );


	namespace Tests\helpers;

    use PHPUnit\Framework\Assert;
    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Http\Responses\NullResponse;

    trait AssertsResponse {


		private function assertStatusCode(int $code, ResponseInterface $response) {

			$this->assertSame($code , $response->getStatusCode());

		}

		private function assertContentType( string $type, ResponseInterface $response ) {

			$this->assertSame($type, $response->getHeaderLine('Content-Type'));

		}

        private function assertOutput ( $output , ResponseInterface $response ) {

            $this->assertSame($output, $response->getBody()->__toString());

        }

		private function assertOutputContains ( $output , ResponseInterface $response ) {

			$this->assertStringContainsString($output, $response->getBody()->__toString());

		}

		private function assertNullResponse( ResponseInterface $response ) {

		    Assert::assertInstanceOf(NullResponse::class, $response);

        }

		private function assertHeader ( $name, $value , ResponseInterface $response) {

			$this->assertSame($value, $response->getHeaderLine($name));

		}

	}