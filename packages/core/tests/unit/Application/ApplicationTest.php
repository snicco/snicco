<?php

declare(strict_types=1);

namespace Tests\Core\unit\Application;

use Throwable;
use Mockery as m;
use RuntimeException;
use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Snicco\Application\Config;
use Snicco\Application\Application;
use Snicco\Shared\ContainerAdapter;
use Tests\Codeception\shared\UnitTest;
use Snicco\Illuminate\IlluminateContainerAdapter;
use Tests\Codeception\shared\helpers\CreateContainer;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class ApplicationTest extends UnitTest
{
    
    use CreateDefaultWpApiMocks;
    use CreateContainer;
    use CreatePsr17Factories;
    
    private ContainerAdapter $container;
    private string           $base_path;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->container = $this->createContainer();
        $this->base_path = __DIR__;
        $this->createDefaultWpApiMocks();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
        m::close();
    }
    
    /** @test */
    public function the_static_constructor_returns_an_application_instance()
    {
        $base_container = $this->createContainer();
        
        $application = Application::create($this->base_path, $base_container);
        
        $this->assertInstanceOf(Application::class, $application);
        
        $this->assertSame($base_container, $application->container());
    }
    
    /** @test
     * @noinspection PhpParamsInspection
     */
    public function an_app_can_not_be_instantiated_without_the_static_constructor()
    {
        $this->expectError();
        $app = new Application(new IlluminateContainerAdapter());
    }
    
    /** @test */
    public function booting_the_app_created_initial_important_classes_in_the_container()
    {
        $app = $this->newApplication();
        
        $this->assertSame($app, $app->container()[Application::class]);
        $this->assertSame($app->container(), $app->container()[ContainerAdapter::class]);
        $this->assertSame($app->config(), $app->container()[Config::class]);
        $this->assertInstanceOf(Config::class, $app->config());
    }
    
    /** @test */
    public function a_valid_app_key_can_be_created()
    {
        $app = $this->newApplication();
        try {
            $app->config()->set('app.key', 'foobar');
            $app->boot();
            $this->fail('booted with invalid key');
        } catch (ConfigurationException $exception) {
            $this->assertStringStartsWith('Your app.key config value is', $exception->getMessage());
        }
        // set this to disable undefined constant WP_CONTENT_DIR;
        $app->config()->set('app.exception_handling', false);
        $app->config()->set('app.key', $key = Application::generateKey());
        $app->boot();
        
        $this->assertStringStartsWith('base64:', $key);
    }
    
    /** @test */
    public function testHasBeenBootstrapped()
    {
        $app = $this->newApplication();
        $this->assertFalse($app->hasBeenBootstrapped());
        $app->boot();
        $this->assertTrue($app->hasBeenBootstrapped());
    }
    
    /** @test */
    public function the_application_cant_be_bootstrapped_twice()
    {
        $app = $this->newApplication();
        
        $app->boot();
        
        try {
            $app->boot();
            
            $this->fail('Application was bootstrapped two times.');
        } catch (ConfigurationException $e) {
            $this->assertStringContainsString('already bootstrapped', $e->getMessage());
        }
    }
    
    /** @test */
    public function user_provided_config_gets_bound_into_the_di_container()
    {
        $app = $this->newApplication();
        
        $app->boot();
        
        $this->assertEquals('bar', $app->config('app.foo'));
    }
    
    /** @test */
    public function users_can_register_service_providers()
    {
        $app = $this->newApplication();
        
        $app->boot();
        
        $this->assertEquals('bar', $app->container()['foo']);
        $this->assertEquals('bar_bootstrapped', $app->container()['foo_bootstrapped']);
    }
    
    /** @test */
    public function custom_container_adapters_can_be_used()
    {
        $container = new TestContainer();
        
        $app = Application::create(__DIR__, $container);
        
        $this->assertSame($container, $app->container());
        $this->assertInstanceOf(TestContainer::class, $app->container());
    }
    
    /** @test */
    public function config_values_can_be_retrieved()
    {
        $app = $this->newApplication();
        
        $app->boot();
        
        $this->assertInstanceOf(
            Config::class,
            $config = $app->resolve(Config::class)
        );
        $this->assertSame('bar', $app->config('app.foo'));
        $this->assertSame('boo', $app->config('app.bar.baz'));
        $this->assertSame('bogus_default', $app->config('app.bogus', 'bogus_default'));
        $this->assertSame($config, $app->config());
    }
    
    /** @test */
    public function the_request_is_captured()
    {
        $app = $this->newApplication();
        
        $app->boot();
        
        $this->assertInstanceOf(Request::class, $app->resolve(Request::class));
    }
    
    /** @test */
    public function testConfigPath()
    {
        $app = $this->newApplication();
        $path = $app->configPath();
        
        $this->assertSame($this->base_path.DS.'config', $path);
        
        $path = $app->configPath('auth');
        
        $this->assertSame($this->base_path.DS.'config'.DS.'auth', $path);
    }
    
    /** @test */
    public function testStoragePath()
    {
        $app = $this->newApplication();
        $app->boot();
        
        $path = $app->storagePath();
        
        $this->assertSame($this->base_path.DS.'storage', $path);
        
        $path = $app->storagePath('framework');
        
        $this->assertSame($this->base_path.DS.'storage'.DS.'framework', $path);
    }
    
    /** @test */
    public function testConfigCachePath()
    {
        $app = $this->newApplication();
        
        $app->boot();
        
        $path = $app->configCachePath();
        
        $this->assertSame(
            $this->base_path.DS.'bootstrap'.DS.'cache'.DS.'__generated::config.json',
            $path
        );
        
        $this->assertFalse($app->isConfigurationCached());
        
        file_put_contents($path, 'foo');
        
        $this->assertTrue($app->isConfigurationCached());
        
        unlink($path);
        
        $this->assertFalse($app->isConfigurationCached());
    }
    
    /** @test */
    public function testDistPath()
    {
        $app = $this->newApplication();
        $app->boot();
        
        $path = $app->distPath();
        $this->assertSame($this->base_path.DS.'dist', $path);
        $path = $app->distPath('js');
        $this->assertSame($this->base_path.DS.'dist'.DS.'js', $path);
        
        $app->config()->set('app.dist', 'custom-dist');
        $this->assertSame($this->base_path.DS.'custom-dist', $app->distPath());
    }
    
    /** @test */
    public function testBasePath()
    {
        $app = $this->newApplication();
        $app->boot();
        
        $this->assertSame($this->base_path, $app->basePath());
        $this->assertSame($this->base_path.DIRECTORY_SEPARATOR.'foo', $app->basePath('foo'));
    }
    
    /** @test */
    public function generateKey()
    {
        $key = Application::generateKey();
        
        $this->assertStringStartsWith('base64:', $key);
        
        try {
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->fail('Generated app key is not compatible.'.PHP_EOL.$e->getMessage());
        }
    }
    
    /** @test */
    public function testEnvironment()
    {
        $app = $this->newApplication();
        $app['env'] = 'testing';
        
        $this->assertSame('testing', $app->environment());
    }
    
    /** @test */
    public function testIsLocal()
    {
        $app = $this->newApplication();
        $app['env'] = 'local';
        
        $this->assertTrue($app->isLocal());
        
        $app['env'] = 'production';
        $this->assertFalse($app->isLocal());
    }
    
    /** @test */
    public function testIsProduction()
    {
        $app = $this->newApplication();
        $app['env'] = 'production';
        
        $this->assertTrue($app->isProduction());
        
        $app['env'] = 'local';
        $this->assertFalse($app->isProduction());
    }
    
    /** @test */
    public function testIsRunningUnitTests()
    {
        $app = $this->newApplication();
        $app['env'] = 'production';
        
        $this->assertFalse($app->isRunningUnitTest());
        
        $app['env'] = 'testing';
        $this->assertTrue($app->isRunningUnitTest());
    }
    
    /** @test */
    public function testIsRunningInConsole()
    {
        $app = $this->newApplication();
        
        $this->assertTrue($app->isRunningInConsole());
    }
    
    /** @test */
    public function test_exception_for_missing_env_value()
    {
        $app = $this->newApplication();
        $this->expectException(RuntimeException::class);
        $app->boot();
        
        $app['env'] = 'foo';
        
        $app->environment();
    }
    
    private function newApplication() :Application
    {
        $app = Application::create($this->base_path, $this->container);
        $app->setResponseFactory($this->psrResponseFactory());
        $app->setUriFactory($this->psrUriFactory());
        $app->setStreamFactory($this->psrStreamFactory());
        $app->setUploadedFileFactory($this->psrUploadedFileFactory());
        $app->setServerRequestFactory($this->psrServerRequestFactory());
        
        return $app;
    }
    
}

class TestContainer implements ContainerAdapter
{
    
    private array $bindings = [];
    
    public function make($abstract, array $parameters = [])
    {
        return $this->bindings[$abstract];
    }
    
    public function swapInstance($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function instance($abstract, $instance)
    {
        $this->bindings[$abstract] = $instance;
    }
    
    public function call($callable, array $parameters = [])
    {
        //
    }
    
    public function bind($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function singleton($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function offsetExists($offset)
    {
        return isset($this->bindings[$offset]);
    }
    
    public function offsetGet($offset)
    {
        return $this->bindings[$offset];
    }
    
    public function offsetSet($offset, $value)
    {
        $this->bindings[$offset] = $value;
    }
    
    public function offsetUnset($offset)
    {
        unset($this->bindings[$offset]);
    }
    
    public function implementation()
    {
        return $this;
    }
    
}

