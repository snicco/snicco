<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Snicco\Support\Str;
use Snicco\Http\MethodField;
use Tests\Core\MiddlewareTestCase;
use Snicco\Middleware\Core\MethodOverride;

class MethodOverrideTest extends MiddlewareTestCase
{
    
    private MethodField    $method_field;
    private MethodOverride $middleware;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->method_field = new MethodField(TEST_APP_KEY);
        $this->middleware = new MethodOverride($this->method_field);
    }
    
    /** @test */
    public function the_method_can_be_overwritten_for_post_requests()
    {
        $value = $this->getRealValue($this->method_field->html('PUT'));
        
        $request = $this->frontendRequest('POST')->withParsedBody([
            '_method' => $value,
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->receivedRequest()->getMethod());
    }
    
    /** @test */
    public function the_method_cant_be_overwritten_for_anything_but_post_requests()
    {
        $value = $this->getRealValue($this->method_field->html('PUT'));
        
        $request = $this->frontendRequest('GET')->withParsedBody([
            '_method' => $value,
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->receivedRequest()->getMethod());
    }
    
    /** @test */
    public function its_not_possible_to_tamper_with_the_value_of_the_method_field_input()
    {
        $value = $this->getRealValue($this->method_field->html('PUT'));
        $tampered = Str::replaceFirst('PUT', 'DELETE', $value);
        
        $request = $this->frontendRequest('POST')->withParsedBody([
            '_method' => $tampered,
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('POST', $this->receivedRequest()->getMethod());
    }
    
    private function getRealValue(string $html) :string
    {
        return Str::between($html, "value='", "'>");
    }
    
}