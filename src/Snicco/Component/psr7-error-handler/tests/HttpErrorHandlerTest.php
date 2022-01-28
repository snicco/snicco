<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests;

use Exception;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\Filter\MultipleFilter;
use Snicco\Component\Psr7ErrorHandler\Filter\VerbosityFilter;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\Psr7ErrorHandler\Filter\CanDisplayFilter;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\SlowDown;
use Snicco\Component\Psr7ErrorHandler\Filter\ContentTypeFilter;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\JsonDisplayer;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\PlainTextDisplayer;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\PlainTextDisplayer2;
use Snicco\Component\Psr7ErrorHandler\Information\HttpInformationProvider;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\TransformContentType;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\TooManyRequestsTransformer;

use function dirname;
use function json_decode;
use function spl_object_hash;
use function file_get_contents;

final class HttpErrorHandlerTest extends TestCase
{
    
    use CreateTestPsr17Factories;
    
    private HttpErrorHandler $error_handler;
    private RequestInterface $base_request;
    private ResponseFactoryInterface $response_factory;
    private array $error_data;
    private SplHashIdentifier $identifier;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->response_factory = $this->psrResponseFactory();
        $this->error_data = json_decode(
            file_get_contents(
                dirname(__DIR__).'/src/resources/error-data.en.json'
            ),
            true
        );
        $this->identifier = new SplHashIdentifier();
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
        $this->assertEquals('text/plain', $response->getHeaderLine('content-type'));
        
        $body = (string) $response->getBody();
        $title = $this->error_data[500]['title'];
        $details = $this->error_data[500]['details'];
        
        $this->assertStringContainsString('plain_text1', $body);
        $this->assertStringContainsString('id:'.$this->identifier->identify($e), $body);
        $this->assertStringContainsString('title:'.$title, $body);
        $this->assertStringContainsString('details:'.$details, $body);
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
        
        $handler = $this->createErrorHandler([
            new PlainTextDisplayer(),
            new PlainTextDisplayer2(),
            new JsonDisplayer(),
        ]);
        
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain'),
        );
        
        $body = (string) $response->getBody();
        
        // First displayer matched.
        $this->assertStringContainsString('plain_text1', $body);
        $this->assertStringNotContainsString('plain_text2', $body);
        
        $handler = $this->createErrorHandler([
            new PlainTextDisplayer(false),
            new PlainTextDisplayer2(),
            new JsonDisplayer(),
        ]);
        
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'text/plain'),
        );
        
        $body = (string) $response->getBody();
        
        // First displayer did not match, using second displayer.
        $this->assertStringNotContainsString('plain_text1', $body);
        $this->assertStringContainsString('plain_text2', $body);
        
        $e = new Exception('Secret message here.');
        $handler = $this->createErrorHandler([
                new PlainTextDisplayer(),
                new PlainTextDisplayer2(),
                new JsonDisplayer(),
            ]
        );
        $response = $handler->handle(
            $e,
            $this->base_request->withHeader('Accept', 'application/json'),
        );
        
        $body = (string) $response->getBody();
        $body = json_decode($body, true);
        
        // json displayer matched because of the header
        $this->assertSame($this->error_data[500]['title'], $body['title']);
        $this->assertSame($this->error_data[500]['details'], $body['details']);
        $this->assertSame($this->identifier->identify($e), $body['identifier']);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
    }
    
    /** @test */
    public function headers_are_added_to_the_response_if_a_http_exception_was_handled()
    {
        $handler = $this->createErrorHandler(
            [],
            [new TooManyRequestsTransformer()]
        );
        
        $e = new SlowDown('slow down.');
        
        $response = $handler->handle($e, $this->base_request);
        
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('10', $response->getHeaderLine('X-Retry-After'));
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
    }
    
    /** @test */
    public function the_content_type_header_can_not_be_overwritten_by_a_transformer()
    {
        $handler = $this->createErrorHandler(
            [new PlainTextDisplayer()],
            [new TransformContentType()]
        );
        
        $e = new Exception('foobar');
        
        $response = $handler->handle($e, $this->base_request->withHeader('Accept', 'text/plain'));
        
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('BAR', $response->getHeaderLine('X-FOO'));
        $this->assertSame('text/plain', $response->getHeaderLine('content-type'));
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
                    'context' => [
                        'exception' => $e,
                        'identifier' => $this->identifier->identify($e),
                    ],
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
        $filters = new MultipleFilter(
            new VerbosityFilter($debug),
            new ContentTypeFilter(),
            new CanDisplayFilter()
        );
        
        $logger = new RequestAwareLogger($logger ? : new NullLogger(), $log_levels);
        
        $information_provider = new HttpInformationProvider($this->error_data, ...$transformers);
        
        return new HttpErrorHandler(
            $this->response_factory,
            $filters,
            $logger,
            $this->identifier,
            $information_provider,
            $displayers
        );
    }
    
}

