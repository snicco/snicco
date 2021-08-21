<?php

declare(strict_types=1);

namespace Tests\integration\Validation;

use Tests\stubs\TestApp;
use Tests\FrameworkTestCase;
use Snicco\Validation\Validator;
use Snicco\Validation\ValidationServiceProvider;
use Snicco\Validation\Middleware\ShareValidatorWithRequest;

class ValidationServiceProviderTest extends FrameworkTestCase
{
    
    public function packageProviders() :array
    {
        return [
            ValidationServiceProvider::class,
        ];
    }
    
    /** @test */
    public function a_validator_can_be_retrieved()
    {
        
        $v = TestApp::resolve(Validator::class);
        
        $this->assertInstanceOf(Validator::class, $v);
        
    }
    
    /** @test */
    public function config_is_bound()
    {
        
        $this->assertSame([], TestApp::config('validation.messages'));
        
    }
    
    /** @test */
    public function global_middleware_is_added()
    {
        
        $middleware = TestApp::config('middleware');
        
        $this->assertContains(ShareValidatorWithRequest::class, $middleware['groups']['global']);
        $this->assertContains(ShareValidatorWithRequest::class, $middleware['unique']);
        
    }
    
}