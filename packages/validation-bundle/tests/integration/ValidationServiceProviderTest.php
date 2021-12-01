<?php

declare(strict_types=1);

namespace Tests\Validation\integration;

use Snicco\Validation\Validator;
use Snicco\Session\SessionServiceProvider;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Validation\ValidationServiceProvider;
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
    
    protected function packageProviders() :array
    {
        return [
            ValidationServiceProvider::class,
            SessionServiceProvider::class,
        ];
    }
    
}