<?php

declare(strict_types=1);

namespace Tests\Codeception\shared;

use PHPUnit\Framework\Assert;
use Snicco\Testing\TestResponse;
use Test\Helpers\CreateContainer;
use Snicco\Component\Core\Utils\Arr;
use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Testing\TestCase as BaseTestCase;
use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Component\Templating\View\View;
use Snicco\Component\HttpRouting\Http\ResponseEmitter;
use Snicco\Component\Core\Application\Application_OLD;

use function add_filter;

class FrameworkTestCase extends BaseTestCase
{
    
    use CreateContainer;
    
    protected array $mail_data;
    
    protected function setUp() :void
    {
        parent::setUp();
        $GLOBALS['test'] = [];
        add_filter('pre_wp_mail', function ($null, array $wp_mail_input) {
            $this->mail_data[] = $wp_mail_input;
            return true;
        }, 10, 2);
        if ( ! defined('TEST_APP_BASE_PATH')) {
            define('TEST_APP_BASE_PATH', __DIR__.'/TestApp');
        }
    }
    
    protected function tearDown() :void
    {
        TestApp::setApplication(null);
        parent::tearDown();
    }
    
    protected function createApplication() :Application_OLD
    {
        $app = TestApp::make(__DIR__.'/TestApp', $this->createContainer());
        $f = new Psr17Factory();
        $app->setServerRequestFactory($f);
        $app->setStreamFactory($f);
        $app->setUploadedFileFactory($f);
        $app->setUriFactory($f);
        $app->setResponseFactory($f);
        
        return $app;
    }
    
    protected function sentResponse() :TestResponse
    {
        $r = $this->app->resolve(ResponseEmitter::class)->response;
        
        if ( ! $r instanceof TestResponse) {
            $this->fail('No response was sent.');
        }
        
        return $r;
    }
    
    protected function pushProvider($provider) :self
    {
        $this->config->prepend('app.providers', $provider);
        
        return $this;
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
        $actual = ($actual instanceof View) ? $actual->toString() : $actual;
        
        $actual = preg_replace("/\r|\n|\t|\s{2,}/", "", $actual);
        
        Assert::assertSame($expected, trim($actual), 'View not rendered correctly.');
    }
    
    protected function withSessionsEnabled() :self
    {
        $this->withAddedConfig('sessions.enabled', true);
        return $this;
    }
    
    protected function customizeConfigProvider(string $config_namespace = '') :CustomizeConfigProvider
    {
        return new CustomizeConfigProvider(
            $this->app->container(),
            $this->app->config(),
            $config_namespace
        );
    }
    
}