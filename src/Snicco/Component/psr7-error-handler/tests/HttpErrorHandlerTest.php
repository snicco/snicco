<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests;

use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Displayer\FallbackDisplayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\CanDisplay;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\ContentType;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Delegating;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Verbosity;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Information\TransformableInformationProvider;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\Psr7ErrorHandler\Log\RequestContext;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\JsonExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\PlainTextExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\PlainTextExceptionDisplayer2;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\SlowDown;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\TooManyRequestsTransformer;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\TransformContentType;
use TypeError;

use function dirname;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function spl_object_hash;

final class HttpErrorHandlerTest extends TestCase
{

    private HttpErrorHandler $error_handler;
    private RequestInterface $base_request;
    private ResponseFactoryInterface $response_factory;
    private array $error_data;
    private SplHashIdentifier $identifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response_factory = new Psr17Factory();
        $this->error_data = json_decode(
            file_get_contents(
                dirname(__DIR__) . '/resources/en_US.error.json'
            ),
            true
        );
        $this->identifier = new SplHashIdentifier();
        $this->error_handler = $this->createErrorHandler();
        $this->base_request = $this->response_factory->createServerRequest('GET', '/');
    }

    /**
     * @test
     */
    public function an_exception_can_be_converted_to_a_response(): void
    {
        $e = new Exception('foobar');

        $response = $this->error_handler->handle($e, $this->base_request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function if_no_displayer_matches_a_fallback_response_will_be_returned(): void
    {
        $e = new Exception('Secret message here.');

        $response = $this->error_handler->handle($e, $this->base_request);

        $this->assertEquals(500, $response->getStatusCode());

        $body = (string)$response->getBody();

        $this->assertStringStartsWith('<h1>Oops! An Error Occurred</h1>', $body);
        $this->assertStringNotContainsString('Secret message here', $body);
        $this->assertStringContainsString(spl_object_hash($e), $body);
    }

    /**
     * @test
     */
    public function the_fallback_response_has_the_correct_status_code_if_no_displayer_matches(): void
    {
        $e = new HttpException(404, 'Secret message here.');

        $response = $this->error_handler->handle($e, $this->base_request);

        $this->assertEquals(404, $response->getStatusCode());

        $body = (string)$response->getBody();

        $this->assertStringStartsWith('<h1>Oops! An Error Occurred</h1>', $body);
        $this->assertStringNotContainsString('Secret message here', $body);
        $this->assertStringContainsString(spl_object_hash($e), $body);
    }

    /**
     * @test
     */
    public function a_custom_displayer_can_handle_the_exception(): void
    {
        $e = new Exception('Secret message here.');

        $response = $this->createErrorHandler([new PlainTextExceptionDisplayer()])->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain'),
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('content-type'));

        $body = (string)$response->getBody();
        $title = $this->error_data[500]['title'];
        $details = $this->error_data[500]['message'];

        $this->assertStringContainsString('plain_text1', $body);
        $this->assertStringContainsString('id:' . $this->identifier->identify($e), $body);
        $this->assertStringContainsString('title:' . $title, $body);
        $this->assertStringContainsString('details:' . $details, $body);
    }

    /**
     * @test
     */
    public function a_custom_displayer_can_also_decide_if_an_individual_exception_can_be_handled(): void
    {
        $e = new Exception('Secret message here.');

        $response = $this->createErrorHandler([new PlainTextExceptionDisplayer(false)])
            ->handle(
                $e,
                $this->base_request->withHeader('Accept', 'text/plain'),
            );

        // default handler handles this.
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringStartsWith(
            '<h1>Oops! An Error Occurred</h1>',
            (string)$response->getBody()
        );
        $this->assertEquals('text/html', $response->getHeaderLine('content-type'));
    }

    /**
     * @test
     */
    public function multiple_displayers_can_be_added_and_the_first_matching_one_will_be_used(): void
    {
        $e = new Exception('Secret message here.');

        $handler = $this->createErrorHandler([
            new PlainTextExceptionDisplayer(),
            new PlainTextExceptionDisplayer2(),
            new JsonExceptionDisplayer(),
        ]);

        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain'),
        );

        $body = (string)$response->getBody();

        // First displayer matched.
        $this->assertStringContainsString('plain_text1', $body);
        $this->assertStringNotContainsString('plain_text2', $body);

        $handler = $this->createErrorHandler([
            new PlainTextExceptionDisplayer(false),
            new PlainTextExceptionDisplayer2(),
            new JsonExceptionDisplayer(),
        ]);

        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain'),
        );

        $body = (string)$response->getBody();

        // First displayer did not match, using second displayer.
        $this->assertStringNotContainsString('plain_text1', $body);
        $this->assertStringContainsString('plain_text2', $body);

        $e = new Exception('Secret message here.');
        $handler = $this->createErrorHandler([
                new PlainTextExceptionDisplayer(),
                new PlainTextExceptionDisplayer2(),
                new JsonExceptionDisplayer(),
            ]
        );
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'application/json'),
        );

        $body = (string)$response->getBody();
        $body = json_decode($body, true);

        // json displayer matched because of the header
        $this->assertSame($this->error_data[500]['title'], $body['title']);
        $this->assertSame($this->error_data[500]['message'], $body['details']);
        $this->assertSame($this->identifier->identify($e), $body['identifier']);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
    }

    /**
     * @test
     */
    public function headers_are_added_to_the_response_if_a_http_exception_was_handled(): void
    {
        $handler = $this->createErrorHandler(
            [],
            [new TooManyRequestsTransformer()]
        );

        $e = new SlowDown('slow down.');

        $response = $handler->handle($e, $this->base_request);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('10', $response->getHeaderLine('X-Retry-After'));
        $this->assertSame('text/html', $response->getHeaderLine('content-type'));
    }

    /**
     * @test
     */
    public function the_content_type_header_can_not_be_overwritten_by_a_transformer(): void
    {
        $handler = $this->createErrorHandler(
            [new PlainTextExceptionDisplayer()],
            [new TransformContentType()]
        );

        $e = new Exception('foobar');

        $response = $handler->handle($e, $this->base_request->withHeader('Accept', 'text/plain'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('BAR', $response->getHeaderLine('X-FOO'));
        $this->assertSame('text/plain', $response->getHeaderLine('content-type'));
    }

    /**
     * @test
     */
    public function exceptions_are_logged(): void
    {
        $handler = $this->createErrorHandler([], [], $logger = new TestLogger());

        $e = new Exception('secret stuff');

        $handler->handle($e, $this->base_request);

        $this->assertTrue($logger->hasErrorRecords());
        $this->assertTrue(
            $logger->hasError(
                [
                    'message' => 'secret stuff',
                    'context' => [
                        'exception' => $e,
                        'identifier' => $this->identifier->identify($e),
                    ],
                ]
            )
        );
    }

    /**
     * @test
     */
    public function a_custom_fallback_display_can_be_provided(): void
    {
        $handler = $this->createErrorHandler([], [], null, new JsonFallbackExceptionDisplayer());

        $e = new Exception('foobar');

        $response = $handler->handle($e, $this->base_request);

        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame('custom_fallback_displayer', json_decode((string)$response->getBody()));
    }

    /**
     * @test
     */
    public function an_exception_during_logging_will_be_logged(): void
    {
        $logger = new RequestAwareLogger(
            $test_logger = new TestLogger(),
            [],
            new RequestContextWithException()
        );

        $handler = new HttpErrorHandler(
            $this->response_factory,
            $logger,
            new TransformableInformationProvider($this->error_data, $this->identifier),
            new FallbackDisplayer(),
            new CanDisplay(),
        );

        $e = new Exception('secret stuff');

        $response = $handler->handle($e, $this->base_request);

        $this->assertTrue($test_logger->hasCriticalRecords());
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('content-type'));
        $this->assertStringStartsWith(
            '<h1>Oops! An Error Occurred</h1>',
            (string)$response->getBody()
        );
    }

    /**
     * @test
     */
    public function an_exception_during_displaying_will_be_converted_into_a_minimal_500_error(): void
    {
        $logger = new RequestAwareLogger(
            $test_logger = new TestLogger(),
        );

        $handler = new HttpErrorHandler(
            $this->response_factory,
            $logger,
            new TransformableInformationProvider($this->error_data, $this->identifier),
            new DisplayerWithException(),
            new CanDisplay(),
        );

        $e = new Exception('secret stuff');

        $response = $handler->handle($e, $this->base_request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/plain', $response->getHeaderLine('content-type'));
        $this->assertSame(
            'Internal Server Error',
            (string)$response->getBody()
        );

        $this->assertTrue(
            $test_logger->hasCriticalThatMatches('/display type error/')
        );
    }

    private function createErrorHandler(
        array $displayers = [],
        array $transformers = [],
        LoggerInterface $logger = null,
        ExceptionDisplayer $fallback = null
    ): HttpErrorHandler {
        $filters = new Delegating(
            new Verbosity(false),
            new ContentType(),
            new CanDisplay()
        );

        $logger = new RequestAwareLogger($logger ?: new NullLogger(), []);

        $information_provider = new TransformableInformationProvider(
            $this->error_data,
            $this->identifier,
            ...$transformers
        );

        return new HttpErrorHandler(
            $this->response_factory,
            $logger,
            $information_provider,
            $fallback ?: new FallbackDisplayer(),
            $filters,
            $displayers
        );
    }

}

class RequestContextWithException implements RequestContext
{

    private int $count = 0;

    public function add(array $context, RequestInterface $request): array
    {
        if ($this->count === 0) {
            $e = new TypeError('bad bad type error.');
            $this->count++;
            throw $e;
        }
        return $context;
    }

}

class JsonFallbackExceptionDisplayer implements ExceptionDisplayer
{

    public function display(ExceptionInformation $exception_information): string
    {
        return json_encode('custom_fallback_displayer');
    }

    public function supportedContentType(): string
    {
        return 'application/json';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return true;
    }

}

class DisplayerWithException implements ExceptionDisplayer
{

    public function display(ExceptionInformation $exception_information): string
    {
        throw new TypeError('display type error.');
    }

    public function supportedContentType(): string
    {
        return 'text/html';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return true;
    }

}