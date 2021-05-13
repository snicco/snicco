<?php


    declare(strict_types = 1);


    namespace Tests\integration\HttpKernel;

    use WPEmerge\Http\Response;

    trait AssertKernelOutput
    {

        private function assertStatusCode(int $code, Response $response) {

            $this->assertSame($code , $response->getStatusCode());

        }

        private function assertContentType( string $type, Response $response ) {

            $this->assertSame($type, $response->getHeaderLine('Content-Type'));

        }

        private function assertOutput ( $expected , string $output ) {

            $this->assertStringContainsString( $expected, $output );

        }

        private function assertHeader ( $name, $value , Response $response) {

            $this->assertSame($value, $response->getHeaderLine($name));

        }

    }