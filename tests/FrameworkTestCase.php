<?php

declare(strict_types=1);

namespace Tests;

use Snicco\Support\Arr;
use Tests\stubs\TestApp;
use PHPUnit\Framework\Assert;
use Snicco\Application\Config;
use Snicco\Shared\ContainerAdapter;
use Snicco\Testing\TestResponse;
use Snicco\Http\ResponseEmitter;
use Snicco\Application\Application;
use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Contracts\ServiceProvider;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Testing\TestCase as BaseTestCase;

class FrameworkTestCase extends BaseTestCase
{
    
    protected array $mail_data;
    
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
        TestApp::setApplication(null);
        parent::tearDown();
    }
    
    protected function createApplication() :Application
    {
        $app = TestApp::make(__DIR__.'/fixtures');
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
        $actual = ($actual instanceof ViewInterface) ? $actual->toString() : $actual;
        
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

class CustomizeConfigProvider extends ServiceProvider
{
    
    private array  $remove  = [];
    private array  $extend  = [];
    private array  $replace = [];
    private string $config_namespace;
    
    public function __construct(ContainerAdapter $container_adapter, Config $config, string $config_namespace = '')
    {
        parent::__construct(
            $container_adapter,
            $config
        );
        $this->config_namespace = $config_namespace;
    }
    
    public function remove(string $key)
    {
        $this->remove[] = ! empty($this->config_namespace) ? "$this->config_namespace.$key" : $key;
    }
    
    public function extend(string $key, $value)
    {
        $this->extend[! empty($this->config_namespace) ? "$this->config_namespace.$key" : $key] =
            $value;
    }
    
    public function replace(string $key, $value)
    {
        $this->replace[! empty($this->config_namespace) ? "$this->config_namespace.$key" : $key] =
            $value;
    }
    
    public function add(string $key, $value)
    {
        $this->replace($key, $value);
    }
    
    public function register() :void
    {
        foreach ($this->remove as $key) {
            $this->config->remove($key);
        }
        
        foreach ($this->extend as $key => $value) {
            $this->config->extend($key, $value);
        }
        
        foreach ($this->replace as $key => $value) {
            $this->config->set($key, $value);
        }
    }
    
    function bootstrap() :void
    {
        //
    }
    
}