<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\ResponsePreparation;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

use function ini_get;

/**
 * @internal
 */
final class ResponsePreparationTest extends TestCase
{
    use CreateTestPsr17Factories;

    private ResponseFactory $factory;

    private ResponsePreparation $preparation;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = $this->createResponseFactory();
        $this->preparation = new ResponsePreparation($this->psrStreamFactory());
        $this->request = new Request($this->psrServerRequestFactory()->createServerRequest('GET', ' /foo'));
    }

    /**
     * @test
     */
    public function the_date_header_is_added_if_not_present(): void
    {
        $response = $this->factory->createResponse();

        $response = $this->preparation->prepare($response, $this->request, []);

        $this->assertSame(gmdate('D, d M Y H:i:s') . ' GMT', $response->getHeaderLine('date'));
    }

    /**
     * @test
     */
    public function the_date_header_is_not_modified_is_present(): void
    {
        $date = gmdate('D, d M Y H:i:s T', time() + 10);
        $response = $this->factory->createResponse()
            ->withHeader('date', $date);

        $response = $this->preparation->prepare($response, $this->request, []);

        $this->assertSame($date, $response->getHeaderLine('date'));
    }

    /**
     * @test
     */
    public function cache_control_defaults_are_added(): void
    {
        $response = $this->factory->createResponse();
        $response = $this->preparation->prepare($response, $this->request, []);

        $this->assertStringContainsString('no-cache', $response->getHeaderLine('cache-control'));
        $this->assertStringContainsString('private', $response->getHeaderLine('cache-control'));
    }

    /**
     * @test
     */
    public function cache_control_is_not_added_if_already_present_or_sent_by_a_call_to_header(): void
    {
        $response = $this->factory->createResponse()
            ->withHeader('cache-control', 'public');
        $response = $this->preparation->prepare($response, $this->request, []);
        $this->assertSame('public', $response->getHeaderLine('cache-control'));

        $response = $this->factory->createResponse();
        $response = $this->preparation->prepare(
            $response,
            $this->request,
            ['Cache-Control: no-cache, must-revalidate, max-age=0']
        );
        $this->assertSame(
            'no-cache, must-revalidate, max-age=0, private',
            $response->getHeaderLine('cache-control')
        );
    }

    /**
     * @test
     */
    public function cache_control_header_is_set_to_private_for_non_s_max_age(): void
    {
        $response = $this->factory->createResponse()
            ->withHeader('cache-control', 'must-revalidate');
        $response = $this->preparation->prepare($response, $this->request, []);
        $this->assertSame('must-revalidate, private', $response->getHeaderLine('cache-control'));

        $response = $this->factory->createResponse()
            ->withHeader('cache-control', 'must-revalidate, s-maxage=10');
        $response = $this->preparation->prepare($response, $this->request, []);
        $this->assertSame('must-revalidate, s-maxage=10', $response->getHeaderLine('cache-control'));
    }

    /**
     * @test
     */
    public function test_cache_control_headers_with_validators_present(): void
    {
        $date = gmdate('D, d M Y H:i:s T', time() + 10);
        $response = $this->factory->createResponse()
            ->withHeader('Expires', $date);
        $response = $this->preparation->prepare($response, $this->request, []);
        $this->assertSame('private, must-revalidate', $response->getHeaderLine('cache-control'));

        $response = $this->factory->createResponse()
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s T', 10));

        $response = $this->preparation->prepare($response, $this->request, []);
        $this->assertSame('private, must-revalidate', $response->getHeaderLine('cache-control'));
    }

    /**
     * @test
     */
    public function test_fixes_informational_responses(): void
    {
        $response = $this->factory->html('foo', 100)
            ->withHeader('content-length', '3');

        $prepared = $this->preparation->prepare($response, $this->request, []);
        $this->assertSame(0, $prepared->getBody()->getSize());
        $this->assertSame('', $prepared->getHeaderLine('content-type'));
        $this->assertSame('', $prepared->getHeaderLine('content-length'));
        $this->assertSame('', ini_get('default_mimetype'));
    }

    /**
     * @test
     */
    public function a_default_content_type_is_added_if_not_present(): void
    {
        $response = $this->factory->createResponse()
            ->withBody($this->factory->createStream('foo'));
        $prepared = $this->preparation->prepare($response, $this->request, []);
        $this->assertSame('text/html; charset=UTF-8', $prepared->getHeaderLine('content-type'));

        // with charset if content type present
        $prepared = $this->preparation->prepare($response->withContentType('text/html'), $this->request, []);
        $this->assertSame('text/html; charset=UTF-8', $prepared->getHeaderLine('content-type'));

        // with charset if content type present with ;
        $prepared = $this->preparation->prepare($response->withContentType('text/html;'), $this->request, []);
        $this->assertSame('text/html; charset=UTF-8', $prepared->getHeaderLine('content-type'));
    }

    /**
     * @test
     */
    public function test_remove_content_length_if_transfer_encoding(): void
    {
        $response = $this->factory->createResponse()
            ->withBody($this->factory->createStream('foo'))
            ->withHeader('content-length', '3')
            ->withHeader('transfer-encoding', 'chunked');

        $prepared = $this->preparation->prepare($response, $this->request, []);

        $this->assertFalse($prepared->hasHeader('content-length'));
    }

    /**
     * @test
     */
    public function the_mode_is_removed_for_head_requests(): void
    {
        $response = $this->factory->html('foo');

        $prepared = $this->preparation->prepare($response, $this->request->withMethod('HEAD'), []);

        $this->assertSame(0, $prepared->getBody()->getSize());
    }

    /**
     * @test
     */
    public function the_content_length_header_is_added(): void
    {
        $response = $this->factory->html(str_repeat('a', 40));

        $prepared = $this->preparation->prepare($response, $this->request, []);

        $this->assertSame('40', $prepared->getHeaderLine('content-length'));
    }

    /**
     * @test
     */
    public function no_content_length_if_output_buffering_is_on_and_has_content(): void
    {
        $response = $this->factory->html(str_repeat('a', 40));
        ob_start();
        echo 'foo';

        $prepared = $this->preparation->prepare($response, $this->request, []);

        $this->assertFalse($prepared->hasHeader('content-length'));
        ob_end_clean();
    }

    /**
     * @test
     */
    public function no_content_length_if_empty_response_stream(): void
    {
        $response = $this->factory->html('');

        $prepared = $this->preparation->prepare($response, $this->request, []);

        $this->assertFalse($prepared->hasHeader('content-length'));
    }
}
