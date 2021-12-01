<?php

namespace Tests\Core\unit\Http;

use Mockery;
use Snicco\Support\Carbon;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Http\ResponsePreparation;
use Tests\Codeception\shared\UnitTest;
use Tests\Core\fixtures\TestDoubles\HeaderStack;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class ResponsePreparationTest extends UnitTest
{
    
    use CreatePsr17Factories;
    
    private ResponseFactory $factory;
    private ResponsePreparation $preparation;
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->factory = $this->createResponseFactory();
        $this->preparation = new ResponsePreparation($this->psrStreamFactory());
        $this->request =
            new Request($this->psrServerRequestFactory()->createServerRequest('GET', ' /foo'));
        HeaderStack::reset();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }
    
    /** @test */
    public function testDateIsSetIfNotSetAlready()
    {
        $response = $this->factory->make();
        
        $response = $this->preparation->prepare($response, $this->request);
        
        $this->assertSame(gmdate('D, d M Y H:i:s').' GMT', $response->getHeaderLine('date'));
    }
    
    /** @test */
    public function testDateIsNotModifiedIfAlreadySet()
    {
        $date = Carbon::now()->subHour()->getTimestamp();
        $response = $this->factory->make()
                                  ->withHeader('date', gmdate('D, d M Y H:i:s', $date).' GMT');
        
        $response = $this->preparation->prepare($response, $this->request);
        
        $this->assertSame(gmdate('D, d M Y H:i:s', $date).' GMT', $response->getHeaderLine('date'));
    }
    
    /** @test */
    public function testCacheControlDefaultsAreAdded()
    {
        $response = $this->factory->make();
        $response = $this->preparation->prepare($response, $this->request);
        
        $this->assertStringContainsString('no-cache', $response->getHeaderLine('cache-control'));
        $this->assertStringContainsString('private', $response->getHeaderLine('cache-control'));
    }
    
    /** @test */
    public function cache_control_is_not_added_if_already_present_or_sent_by_a_call_to_header()
    {
        $response = $this->factory->make()->withHeader('cache-control', 'public');
        $response = $this->preparation->prepare($response, $this->request);
        $this->assertSame('public', $response->getHeaderLine('cache-control'));
        
        HeaderStack::push([
            'header' => 'Cache-Control: no-cache, must-revalidate, max-age=0',
            'replace' => false,
            'status_code' => null,
        ]);
        
        $response = $this->factory->make();
        $response = $this->preparation->prepare($response, $this->request);
        $this->assertSame(
            'no-cache, must-revalidate, max-age=0, private',
            $response->getHeaderLine('cache-control')
        );
        
        HeaderStack::reset();
    }
    
    /** @test */
    public function testCacheControlHeadersWithValidatorsPresent()
    {
        $response = $this->factory->make()->withHeader('Expires', Carbon::expires(10));
        $response = $this->preparation->prepare($response, $this->request);
        $this->assertSame('private, must-revalidate', $response->getHeaderLine('cache-control'));
        
        $response = $this->factory->make()
                                  ->withHeader('Last-Modified', Carbon::lastModified(-1000));
        
        $response = $this->preparation->prepare($response, $this->request);
        $this->assertSame('private, must-revalidate', $response->getHeaderLine('cache-control'));
    }
    
    /** @test */
    public function testFixesInformationalResponses()
    {
        $response = $this->factory->html('foo', 100)
                                  ->withHeader('content-length', 3);
        
        $prepared = $this->preparation->prepare($response, $this->request);
        $this->assertSame(0, $prepared->getBody()->getSize());
        $this->assertSame('', $prepared->getHeaderLine('content-type'));
        $this->assertSame('', $prepared->getHeaderLine('content-length'));
        $this->assertSame('', ini_get('default_mimetype'));
    }
    
    /** @test */
    public function testAddContentTypeIfNotPresent()
    {
        $response = $this->factory->make()->withBody($this->factory->createStream('foo'));
        $prepared = $this->preparation->prepare($response, $this->request);
        $this->assertSame('text/html; charset=UTF-8', $prepared->getHeaderLine('content-type'));
        
        // with charset if content type present
        $prepared = $this->preparation->prepare(
            $response->withContentType('text/html'),
            $this->request
        );
        $this->assertSame('text/html; charset=UTF-8', $prepared->getHeaderLine('content-type'));
        
        // with charset if content type present with ;
        $prepared = $this->preparation->prepare(
            $response->withContentType('text/html;'),
            $this->request
        );
        $this->assertSame('text/html; charset=UTF-8', $prepared->getHeaderLine('content-type'));
    }
    
    /** @test */
    public function testRemoveContentLengthIfTransferEncoding()
    {
        $response = $this->factory->make()
                                  ->withBody($this->factory->createStream('foo'))
                                  ->withHeader('content-length', 3)
                                  ->withHeader('transfer-encoding', 'chunked');
        
        $prepared = $this->preparation->prepare($response, $this->request);
        
        $this->assertFalse($prepared->hasHeader('content-length'));
    }
    
    /** @test */
    public function testHeadRequestRemovesBody()
    {
        $response = $this->factory->html('foo');
        
        $prepared = $this->preparation->prepare($response, $this->request->withMethod('HEAD'));
        
        $this->assertSame(0, $prepared->getBody()->getSize());
    }
    
    /** @test */
    public function testContentLength()
    {
        $response = $this->factory->html(str_repeat('a', 40));
        
        $prepared = $this->preparation->prepare($response, $this->request);
        
        $this->assertSame('40', $prepared->getHeaderLine('content-length'));
    }
    
    /** @test */
    public function no_content_length_if_output_buffering_is_on_and_has_content()
    {
        $response = $this->factory->html(str_repeat('a', 40));
        ob_start();
        echo 'foo';
        
        $prepared = $this->preparation->prepare($response, $this->request);
        
        $this->assertFalse($prepared->hasHeader('content-length'));
        ob_end_clean();
    }
    
    /** @test */
    public function no_content_length_if_empty_response_stream()
    {
        $response = $this->factory->html('');
        
        $prepared = $this->preparation->prepare($response, $this->request);
        
        $this->assertFalse($prepared->hasHeader('content-length'));
    }
    
    /** @test */
    public function no_content_length_for_wp_admin_page_requests()
    {
        $response = $this->factory->html('foobar');
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest(
                'GET',
                ' /wp-admin/admin.php?page=test',
                ['SCRIPT_NAME' => 'wp-admin/admin.php']
            )
        );
        
        $prepared = $this->preparation->prepare($response, $request);
        
        $this->assertFalse($prepared->hasHeader('content-length'));
    }
    
    protected function newUrlGenerator(Request $request = null, bool $trailing_slash = false) :UrlGenerator
    {
        return Mockery::mock(UrlGenerator::class);
    }
    
}