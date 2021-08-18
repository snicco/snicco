<?php

declare(strict_types=1);

namespace Tests\unit\Middleware;

use Tests\UnitTest;
use Snicco\Support\Str;
use Snicco\Http\Delegate;
use Snicco\View\MethodField;
use Tests\stubs\TestRequest;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Tests\helpers\AssertsResponse;
use Snicco\Middleware\Authenticate;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateRouteCollection;
use Snicco\Middleware\Core\MethodOverride;

class MethodOverrideTest extends UnitTest
{
    
    use AssertsResponse;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    
    private Authenticate    $middleware;
    private Delegate        $route_action;
    private TestRequest     $request;
    private ResponseFactory $response;
    private MethodField     $method_field;
    
    /** @test */
    public function the_method_can_be_overwritten_for_post_requests()
    {
        
        $value = $this->getRealValue($this->method_field->html('PUT'));
        
        $request = TestRequest::from('POST', '/foo')->withParsedBody([
            '_method' => $value,
        ]);
        
        $response = $this->newMiddleware()->handle($request, $this->route_action);
        
        $this->assertOutput('PUT', $response);
        
    }
    
    private function getRealValue(string $html) :string
    {
        return Str::between($html, "value='", "'>");
    }
    
    private function newMiddleware() :MethodOverride
    {
        
        return new MethodOverride($this->method_field, $this->createContainer());
        
    }
    
    /** @test */
    public function the_method_cant_be_overwritten_for_anything_but_post_requests()
    {
        
        $value = $this->getRealValue($this->method_field->html('PUT'));
        
        $request = TestRequest::from('GET', '/foo')->withParsedBody([
            '_method' => $value,
        ]);
        $response = $this->newMiddleware()->handle($request, $this->route_action);
        $this->assertOutput('GET', $response);
        
        $request = TestRequest::from('PATCH', '/foo')->withParsedBody([
            '_method' => $value,
        ]);
        $response = $this->newMiddleware()->handle($request, $this->route_action);
        $this->assertOutput('PATCH', $response);
        
    }
    
    /** @test */
    public function its_not_possible_to_tamper_with_the_value_of_the_method_field_input()
    {
        
        $value = $this->getRealValue($this->method_field->html('PUT'));
        $tampered = Str::replaceFirst('PUT', 'DELETE', $value);
        
        $request = TestRequest::from('POST', '/foo')->withParsedBody([
            '_method' => $tampered,
        ]);
        
        $response = $this->newMiddleware()->handle($request, $this->route_action);
        $this->assertOutput('POST', $response);
        
    }
    
    protected function beforeTestRun()
    {
        
        $response = $this->createResponseFactory();
        $this->route_action =
            new Delegate(fn(Request $request) => $response->html($request->getMethod()));
        
        $this->method_field = new MethodField(TEST_APP_KEY);
        
    }
    
}