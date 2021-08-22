<?php

declare(strict_types=1);

namespace Tests\integration\Validation;

use Tests\stubs\TestApp;
use BadMethodCallException;
use Tests\FrameworkTestCase;
use Snicco\Validation\Validator;
use Snicco\Testing\TestResponse;
use Snicco\Contracts\ExceptionHandler;
use Snicco\Session\SessionServiceProvider;
use Snicco\Validation\ValidationServiceProvider;
use Snicco\Validation\Exceptions\ValidationException;
use Snicco\Validation\Middleware\ShareValidatorWithRequest;

class ValidationServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function a_validator_can_be_retrieved()
    {
        $this->bootApp();
        $v = TestApp::resolve(Validator::class);
        
        $this->assertInstanceOf(Validator::class, $v);
        
    }
    
    /** @test */
    public function config_is_bound()
    {
        $this->bootApp();
        $this->assertSame([], TestApp::config('validation.messages'));
    }
    
    /** @test */
    public function global_middleware_is_added()
    {
        $this->bootApp();
        $middleware = TestApp::config('middleware');
        
        $this->assertContains(ShareValidatorWithRequest::class, $middleware['groups']['global']);
        $this->assertContains(ShareValidatorWithRequest::class, $middleware['unique']);
    }
    
    /** @test */
    public function validation_exceptions_have_custom_rendering_logic()
    {
        
        $this->withAddedConfig('session.enabled', false);
        $this->bootApp();
        
        /** @var ExceptionHandler $handler */
        $handler = TestApp::resolve(ExceptionHandler::class);
        
        $this->withRequest($this->request->withAddedHeader('referer', '/foobar'));
        
        $response = $handler->toHttpResponse(
            $e = ValidationException::withMessages(['foo' => 'bar']),
            $this->request
        );
        
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/foobar', $response->getHeaderLine('location'));
        
        // sessions not enabled.
        $this->expectException(BadMethodCallException::class);
        TestApp::session();
        
    }
    
    /** @test */
    public function errors_are_flashed_if_session_are_enabled()
    {
        $this->withSessionsEnabled();
        $this->bootApp();
        
        /** @var ExceptionHandler $handler */
        $handler = TestApp::resolve(ExceptionHandler::class);
        
        $this->withRequest($this->request->withAddedHeader('referer', '/foobar'));
        
        $response = $handler->toHttpResponse(
            $e = ValidationException::withMessages(['foo' => 'bar']),
            $this->request
        );
        
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/foobar', $response->getHeaderLine('location'));
        
        // sessions enabled.
        $this->assertTrue($this->session->errors()->has('foo'));
        
    }
    
    /** @test */
    public function test_validation_exception_rendering_with_json()
    {
        
        $this->bootApp();
        
        /** @var ExceptionHandler $handler */
        $handler = TestApp::resolve(ExceptionHandler::class);
        
        $this->withRequest(
            $this->request
                ->withAddedHeader('referer', '/foobar')
                ->withHeader('Accept', 'application/json')
        );
        
        $response = new TestResponse(
            $handler->toHttpResponse(
                $e = ValidationException::withMessages(['foo' => 'bar'], 'Sorry.')->withStatusCode(
                    422
                ),
                $this->request
            )
        );
        
        $response->assertStatus(422);
        $response->assertIsJson();
        
        $content = json_decode($response->body(), true);
        $this->assertSame('Sorry.', $content['message']);
        $this->assertSame(['foo' => 'bar'], $content['errors']);
        
    }
    
    protected function packageProviders() :array
    {
        return [
            ValidationServiceProvider::class,
            SessionServiceProvider::class,
        ];
    }
    
}