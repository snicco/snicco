<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Responsable;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Tests\helpers\CreateUrlGenerator;
use stdClass;

use function dirname;
use function fopen;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class ResponseFactoryTest extends TestCase
{
    use CreateTestPsr17Factories;
    use CreateUrlGenerator;

    private ResponseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = $this->createResponseFactory();
    }

    /**
     * @test
     */
    public function make(): void
    {
        $response = $this->factory->createResponse(204, 'Hello');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('Hello', $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function json(): void
    {
        $response = $this->factory->json([
            'foo' => 'bar',
        ], 401);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode([
            'foo' => 'bar',
        ]), (string) $response->getBody());
    }

    /**
     * @test
     */
    public function test_json_throws_even_if_not_set_as_option(): void
    {
        $this->expectException(JsonException::class);
        $this->factory->json("\xB1\x31", 401, JSON_PRETTY_PRINT);
    }

    /**
     * @test
     */
    public function test_json_throws_if_set_as_option(): void
    {
        $this->expectException(JsonException::class);
        $this->factory->json("\xB1\x31", 401, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @test
     */
    public function test_to_response_for_response(): void
    {
        $response = $this->factory->createResponse();
        $result = $this->factory->toResponse($response);
        $this->assertSame($result, $response);
    }

    /**
     * @test
     */
    public function test_to_response_for_psr7_response(): void
    {
        $response = $this->psrResponseFactory()
            ->createResponse();
        $result = $this->factory->toResponse($response);
        $this->assertNotSame($result, $response);
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * @test
     */
    public function test_to_response_for_string(): void
    {
        $response = $this->factory->toResponse('foo');
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function test_to_response_for_array(): void
    {
        $input = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $response = $this->factory->toResponse($input);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));

        $this->assertSame(json_encode($input), (string) $response->getBody());
    }

    /**
     * @test
     */
    public function test_to_response_for_stdclass(): void
    {
        $input = new stdClass();
        $input->foo = 'bar';

        $response = $this->factory->toResponse($input);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode([
            'foo' => 'bar',
        ]), $response->getBody()
            ->__toString());
    }

    /**
     * @test
     */
    public function test_to_response_for_responseable(): void
    {
        $class = new class() implements Responsable {
            public function toResponsable(): string
            {
                return 'foo';
            }
        };

        $response = $this->factory->toResponse($class);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', (string) $response->getBody());
    }

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function to_response_throws_an_exception_if_no_response_can_be_created(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->toResponse(1);
    }

    /**
     * @test
     */
    public function test_no_content(): void
    {
        $response = $this->factory->noContent();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_redirect(): void
    {
        $response = $this->factory->redirect('/foo', 307);

        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/foo', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_exception_for_status_code_that_is_to_low(): void
    {
        $this->assertInstanceOf(Response::class, $this->factory->createResponse(100));
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createResponse(99);
    }

    /**
     * @test
     */
    public function test_exception_for_status_code_that_is_to_high(): void
    {
        $this->assertInstanceOf(Response::class, $this->factory->createResponse(599));
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createResponse(600);
    }

    /**
     * @test
     */
    public function test_delegate(): void
    {
        $this->assertTrue($this->factory->delegate()->shouldHeadersBeSent());
        $this->assertFalse($this->factory->delegate(false)->shouldHeadersBeSent());
    }

    /**
     * @test
     */
    public function test_create_stream_from_file(): void
    {
        $stream = $this->factory->createStreamFromFile(dirname(__DIR__) . '/fixtures/stream/foo.txt');
        $this->assertSame(3, $stream->getSize());
        $this->assertSame('foo', $stream->getContents());
    }

    /**
     * @test
     */
    public function test_create_stream_from_resource(): void
    {
        $file = dirname(__DIR__) . '/fixtures/stream/foo.txt';
        /** @psalm-suppress PossiblyFalseArgument */
        $stream = $this->factory->createStreamFromResource(fopen($file, 'r'));
        $this->assertSame(3, $stream->getSize());
        $this->assertSame('foo', $stream->getContents());
    }

    /**
     * @test
     */
    public function test_view_adds_content_type(): void
    {
        $response = $this->factory->view('foo.php', [
            'foo' => 'bar',
        ]);
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
    }
}
