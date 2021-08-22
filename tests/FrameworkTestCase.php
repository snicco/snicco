<?php

declare(strict_types=1);

namespace Tests;

use Snicco\Support\Arr;
use Tests\stubs\TestApp;
use PHPUnit\Framework\Assert;
use Snicco\Testing\TestResponse;
use Snicco\Http\ResponseEmitter;
use Snicco\Application\Application;
use Snicco\Contracts\ViewInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Testing\TestCase as BaseTestCase;

class FrameworkTestCase extends BaseTestCase
{
    
    protected array $mail_data;
    
    protected function createApplication() :Application
    {
        $app = TestApp::make(FIXTURES_DIR);
        $f = new Psr17Factory();
        $app->setServerRequestFactory($f);
        $app->setStreamFactory($f);
        $app->setUploadedFileFactory($f);
        $app->setUriFactory($f);
        $app->setResponseFactory($f);
        
        return $app;
    }
    
    protected function setUp() :void
    {
        parent::setUp();
        $GLOBALS['test'] = [];
        add_filter('pre_wp_mail', function ($null, array $wp_mail_input) {
            $this->mail_data[] = $wp_mail_input;
            return true;
        }, 10, 2);
    }
    
    protected function tearDown() :void
    {
        $GLOBALS['test'] = [];
        TestApp::setApplication(null);
        parent::tearDown();
    }
    
    protected function sentResponse() :TestResponse
    {
        
        $r = $this->app->resolve(ResponseEmitter::class)->response;
        
        if ( ! $r instanceof TestResponse) {
            $this->fail('No response was sent.');
        }
        
        return $r;
        
    }
    
    protected function withAddedProvider($provider) :self
    {
        $provider = Arr::wrap($provider);
        
        foreach ($provider as $p) {
            
            $this->withAddedConfig(['app.providers' => [$p]]);
            
        }
        
        return $this;
    }
    
    protected function withRemovedProvider($provider) :self
    {
        
        $provider = Arr::wrap($provider);
        
        $providers = $this->app->config('app.providers');
        
        foreach ($provider as $p) {
            
            Arr::pullByValue($p, $providers);
            
        }
        
        $this->withReplacedConfig('app.providers', $providers);
        
        return $this;
    }
    
    protected function withoutHooks() :self
    {
        $GLOBALS['wp_filter'] = [];
        $GLOBALS['wp_actions'] = [];
        $GLOBALS['wp_current_filter'] = [];
        
        return $this;
    }
    
    protected function assertNoResponse()
    {
        $this->assertNull($this->app->resolve(ResponseEmitter::class)->response);
    }
    
    protected function assertViewContent(string $expected, $actual)
    {
        $actual = ($actual instanceof ViewInterface) ? $actual->toString() : $actual;
        
        $actual = preg_replace("/\r|\n|\t|\s{2,}/", "", $actual);
        
        Assert::assertSame($expected, trim($actual), 'View not rendered correctly.');
    }
    
    protected function bootAfterCreation()
    {
        $this->afterApplicationCreated(function () {
            $this->bootApp();
        });
    }
    
    protected function withSessionsEnabled() :self
    {
        $this->withAddedConfig('sessions.enabled', true);
        return $this;
    }
    
}