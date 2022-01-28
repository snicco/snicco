<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http\ErrorHandler;

use Exception;
use Throwable;
use TypeError;
use RuntimeException;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Displayer;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Transformer;
use Snicco\Component\HttpRouting\Http\ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Http\ErrorHandler\HttpErrorHandler;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Filter\MultipleFilter;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Filter\VerbosityFilter;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Filter\CanDisplayFilter;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Filter\ContentTypeFilter;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Identifier\SplHashIdentifier;

use function json_encode;
use function json_decode;
use function spl_object_hash;

final class HttpErrorHandlerTest extends TestCase
{
    
    use CreateTestPsr17Factories;
    
    private HttpErrorHandler $error_handler;
    private RequestInterface $base_request;
    private ResponseFactoryInterface $response_factory;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->response_factory = $this->psrResponseFactory();
        $this->error_handler = $this->createErrorHandler();
        $this->base_request = $this->psrServerRequestFactory()->createServerRequest('GET', '/');
    }
    
    /** @test */
    public function an_exception_can_be_converted_to_a_response()
    {
        $e = new Exception('foobar');
        
        $response = $this->error_handler->handle($e, $this->base_request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
    
    /** @test */
    public function if_no_displayer_matches_a_fallback_response_will_be_returned()
    {
        $e = new Exception('Secret message here.');
        
        $response = $this->error_handler->handle($e, $this->base_request);
        
        $this->assertEquals(500, $response->getStatusCode());
        
        $body = (string) $response->getBody();
        
        $this->assertStringStartsWith('<h1>Oops! An Error Occurred</h1>', $body);
        $this->assertStringNotContainsString('Secret message here', $body);
        $this->assertStringContainsString(spl_object_hash($e), $body);
    }
    
    /** @test */
    public function the_fallback_response_has_the_correct_status_code_if_no_displayer_matches()
    {
        $e = new HttpException(404, 'Secret message here.');
        
        $response = $this->error_handler->handle($e, $this->base_request);
        
        $this->assertEquals(404, $response->getStatusCode());
        
        $body = (string) $response->getBody();
        
        $this->assertStringStartsWith('<h1>Oops! An Error Occurred</h1>', $body);
        $this->assertStringNotContainsString('Secret message here', $body);
        $this->assertStringContainsString(spl_object_hash($e), $body);
    }
    
    /** @test */
    public function a_custom_displayer_can_handle_the_exception()
    {
        $e = new Exception('Secret message here.');
        
        $response = $this->createErrorHandler([new PlainTextDisplayer()])->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain'),
        );
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('500:Internal Server Error', (string) $response->getBody());
        $this->assertEquals('text/plain', $response->getHeaderLine('content-type'));
    }
    
    /** @test */
    public function a_custom_displayer_can_also_decide_if_an_individual_exception_can_be_handled()
    {
        $e = new Exception('Secret message here.');
        
        $response = $this->createErrorHandler([new PlainTextDisplayer(false)])
                         ->handle(
                             $e,
                             $this->base_request->withHeader('Accept', 'text/plain'),
                         );
        
        // default handler handles this.
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringStartsWith(
            '<h1>Oops! An Error Occurred</h1>',
            (string) $response->getBody()
        );
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
    }
    
    /** @test */
    public function multiple_displayers_can_be_added_and_the_first_matching_one_will_be_used()
    {
        $e = new Exception('Secret message here.');
        
        $handler = $this->createErrorHandler([new PlainTextDisplayer(), new JsonDisplayer()]);
        
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain'),
        );
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('500:Internal Server Error', (string) $response->getBody());
        $this->assertEquals('text/plain', $response->getHeaderLine('content-type'));
        
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'application/json'),
        );
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(
            ['message' => 'Oops.Error'],
            json_decode((string) $response->getBody(), true)
        );
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
    }
    
    /** @test */
    public function a_transformer_can_change_the_exception()
    {
        $e = new InvalidArgumentException('message');
        
        $handler = $this->createErrorHandler(
            [new PlainTextDisplayer()],
            [new InvalidArgumentTransformer()]
        );
        
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain'),
        );
        
        $this->assertEquals(504, $response->getStatusCode());
        $this->assertEquals('504:new message', (string) $response->getBody());
    }
    
    /** @test */
    public function verbose_displayers_are_not_considered_in_production()
    {
        $e = new InvalidArgumentException('message');
        
        $handler = $this->createErrorHandler(
            [
                new VerbosePlainTextDisplayer(),
                new PlainTextDisplayer(),
            ]
        );
        
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain')
        );
        
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('500:Internal Server Error', (string) $response->getBody());
    }
    
    /** @test */
    public function verbose_displayers_are_preferred_in_production()
    {
        $e = new InvalidArgumentException('message');
        
        $handler = $this->createErrorHandler(
            [
                new VerbosePlainTextDisplayer(),
                new PlainTextDisplayer(),
            ],
            [],
            true
        );
        
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain')
        );
        
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('500:VERBOSE:Internal Server Error', (string) $response->getBody());
    }
    
    /** @test */
    public function the_correct_exception_identifier_is_passed_for_transformed_exceptions()
    {
        $handler = $this->createErrorHandler(
            [new WithIdDisplayer()],
            [new InvalidArgumentTransformer()]
        );
        
        $e = new InvalidArgumentException('foobar');
        
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('accept', 'text/plain')
        );
        
        $this->assertSame('504:'.spl_object_hash($e), (string) $response->getBody());
    }
    
    /** @test */
    public function headers_from_the_http_exception_class_are_considered()
    {
        $handler = $this->createErrorHandler(
            [],
            [new TooManyRequestsTransformer()]
        );
        
        $e = new RuntimeException('slow down.');
        
        $response = $handler->handle($e, $this->base_request);
        
        $this->assertEquals('BAR', $response->getHeaderLine('X-FOO'));
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
    }
    
    /** @test */
    public function exceptions_are_logged()
    {
        $handler = $this->createErrorHandler([], [], false, $logger = new TestLogger());
        
        $e = new Exception('secret stuff');
        
        $handler->handle($e, $this->base_request);
        
        $this->assertTrue($logger->hasErrorRecords());
        $this->assertTrue(
            $logger->hasError(
                [
                    'message' => 'secret stuff',
                    'context' => ['exception' => $e, 'identifier' => spl_object_hash($e)],
                ]
            )
        );
    }
    
    /** @test */
    public function throwables_are_logged_with_log_level_critical()
    {
        $handler = $this->createErrorHandler([], [], false, $logger = new TestLogger());
        
        $e = new TypeError('secret stuff');
        
        $handler->handle($e, $this->base_request);
        
        $this->assertTrue(
            $logger->hasCritical(
                [
                    'message' => 'secret stuff',
                    'context' => ['exception' => $e, 'identifier' => spl_object_hash($e)],
                ]
            )
        );
    }
    
    /** @test */
    public function error_levels_can_be_customized()
    {
        $handler = $this->createErrorHandler(
            [],
            [],
            false,
            $logger = new TestLogger(),
            [
                InvalidArgumentException::class => LogLevel::CRITICAL,
            ]
        );
        
        $e = new InvalidArgumentException('secret stuff');
        
        $handler->handle($e, $this->base_request);
        
        $this->assertTrue(
            $logger->hasCritical(
                [
                    'message' => 'secret stuff',
                    'context' => ['exception' => $e, 'identifier' => spl_object_hash($e)],
                ]
            )
        );
    }
    
    private function createErrorHandler(
        array $displayers = [],
        array $transformers = [],
        bool $debug = false,
        LoggerInterface $logger = null,
        array $log_levels = []
    ) :HttpErrorHandler {
        return new HttpErrorHandler(
            $this->response_factory,
            new MultipleFilter(
                new VerbosityFilter($debug),
                new ContentTypeFilter(),
                new CanDisplayFilter()
            ),
            new RequestAwareLogger($logger ? : new NullLogger(), $log_levels),
            new SplHashIdentifier(),
            $displayers,
            $transformers,
        );
    }
    
}

class TooManyRequestsTransformer implements Transformer
{
    
    public function transform(Throwable $e) :Throwable
    {
        if ( ! $e instanceof RuntimeException) {
            return $e;
        }
        
        return HttpException::fromPrevious(429, $e, ['X-FOO' => 'BAR', 'content-type' => 'bogus']);
    }
    
}

class InvalidArgumentTransformer implements Transformer
{
    
    public function transform(Throwable $e) :Throwable
    {
        if ( ! $e instanceof InvalidArgumentException) {
            return $e;
        }
        
        return new HttpException(504, 'new message', [], 0, $e);
    }
    
}

class PlainTextDisplayer implements Displayer
{
    
    private bool $should_handle;
    
    public function __construct(bool $should_handle = true)
    {
        $this->should_handle = $should_handle;
    }
    
    public function display(HttpException $e, string $identifier) :string
    {
        return $e->statusCode().':'.$e->getMessage();
    }
    
    public function supportedContentType() :string
    {
        return 'text/plain';
    }
    
    public function isVerbose() :bool
    {
        return false;
    }
    
    public function canDisplay(HttpException $e) :bool
    {
        return $this->should_handle;
    }
    
}

class WithIdDisplayer implements Displayer
{
    
    public function display(HttpException $e, string $identifier) :string
    {
        return $e->statusCode().':'.$identifier;
    }
    
    public function supportedContentType() :string
    {
        return 'text/plain';
    }
    
    public function isVerbose() :bool
    {
        return false;
    }
    
    public function canDisplay(HttpException $e) :bool
    {
        return true;
    }
    
}

class VerbosePlainTextDisplayer implements Displayer
{
    
    private bool $should_handle;
    
    public function __construct(bool $should_handle = true)
    {
        $this->should_handle = $should_handle;
    }
    
    public function display(HttpException $e, string $identifier) :string
    {
        return $e->statusCode().':VERBOSE:'.$e->getMessage();
    }
    
    public function supportedContentType() :string
    {
        return 'text/plain';
    }
    
    public function isVerbose() :bool
    {
        return true;
    }
    
    public function canDisplay(HttpException $e) :bool
    {
        return $this->should_handle;
    }
    
}

class JsonDisplayer implements Displayer
{
    
    public function display(HttpException $e, string $identifier) :string
    {
        return json_encode(['message' => 'Oops.Error']);
    }
    
    public function supportedContentType() :string
    {
        return 'application/json';
    }
    
    public function isVerbose() :bool
    {
        return false;
    }
    
    public function canDisplay(HttpException $e) :bool
    {
        return true;
    }
    
}