<?php


	declare( strict_types = 1 );


	namespace Tests\helpers;

    use PHPUnit\Framework\Assert;
    use Psr\Http\Message\ResponseInterface;
    use Snicco\Http\Responses\NullResponse;

    /**
     * @internal
     */
    trait AssertsResponse {


		protected function assertStatusCode(int $code, ResponseInterface $response) {

			$this->assertSame($code , $response->getStatusCode());

		}

		protected function assertContentType( string $type, ResponseInterface $response ) {

			$this->assertSame($type, $response->getHeaderLine('Content-Type'));

		}

        protected function assertOutput ( $output , ResponseInterface $response ) {

            $this->assertSame($output, $response->getBody()->__toString());

        }

		protected function assertOutputContains ( $output , ResponseInterface $response ) {

			$this->assertStringContainsString($output, $response->getBody()->__toString());

		}

		protected function assertNullResponse( ResponseInterface $response ) {

		    Assert::assertInstanceOf(NullResponse::class, $response);

        }

		protected function assertHeader ( $name, $value , ResponseInterface $response) {

			$this->assertSame($value, $response->getHeaderLine($name));

		}

	}