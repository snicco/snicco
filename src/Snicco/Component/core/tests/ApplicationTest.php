<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Core\Plugin;
use Psr\Container\ContainerInterface;
use Snicco\Component\Core\Directories;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Configuration\ReadOnlyConfig;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\Component\Core\Tests\helpers\CreateTestContainer;

final class ApplicationTest extends TestCase
{
    
    use CreateTestContainer;
    
    private string $base_dir;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->base_dir = __DIR__.'/fixtures';
    }
    
    /** @test */
    public function test_construct()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        $this->assertInstanceOf(Application::class, $app);
    }
    
    /** @test */
    public function test_array_access_proxies_to_set_container()
    {
        $container = $this->createContainer();
        $container['foo'] = 'bar';
        
        $app = new Application(
            $container,
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $this->assertTrue(isset($app['foo']));
        $this->assertTrue(isset($container['foo']));
        $this->assertFalse(isset($app['baz']));
        $this->assertFalse(isset($container['baz']));
        
        $this->assertSame('bar', $app['foo']);
        $this->assertSame('bar', $container['foo']);
        
        unset($app['foo']);
        $this->assertFalse(isset($app['foo']));
        $this->assertFalse(isset($container['foo']));
        
        $app['baz'] = 'biz';
        
        $this->assertTrue(isset($app['baz']));
        $this->assertTrue(isset($container['baz']));
    }
    
    /** @test */
    public function test_env_returns_environment()
    {
        $app = new Application(
            $this->createContainer(),
            $env = Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $this->assertEquals($env, $app->env());
    }
    
    /** @test */
    public function the_environment_is_not_saved_in_the_container_so_that_it_cannot_be_overwritten()
    {
        $app = new Application(
            $container = $this->createContainer(),
            $env = Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $this->assertFalse($container->has(Environment::class));
    }
    
    /** @test */
    public function the_container_is_accessible()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        $this->assertSame($container, $app->di());
    }
    
    /** @test */
    public function the_app_dirs_are_accessible()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            $dirs = Directories::fromDefaults($this->base_dir)
        );
        $this->assertSame($container, $app->di());
        
        $this->assertEquals($dirs, $app->directories());
    }
    
    /** @test */
    public function the_container_is_bound_as_the_psr_container_interface()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $this->assertSame($container, $app[ContainerInterface::class]);
    }
    
    /** @test */
    public function the_application_cant_be_bootstrapped_twice()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $app->boot();
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The application cant be booted twice.");
        
        $app->boot();
    }
    
    /** @test */
    public function booting_the_application_runs_all_bootstrappers_and_plugins()
    {
        $plugin = new DummyPlugin();
        
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir),
            [$plugin]
        );
        
        $this->assertFalse($plugin->booted);
        $this->assertFalse($plugin->configured);
        $this->assertFalse($plugin->registered);
        
        $app->boot();
        
        $this->assertTrue($plugin->booted);
        $this->assertTrue($plugin->configured);
        $this->assertTrue($plugin->registered);
        
        $received_config = $plugin->config;
        $this->assertSame('bar', $received_config['app.foo']);
    }
    
    /**
     * @test
     */
    public function test_config_on_the_app_returns_are_read_only_config()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        
        try {
            $config = $app->config();
        } catch (LogicException $e) {
            $this->assertStringContainsString(
                'only be accessed after bootstrapping.',
                $e->getMessage()
            );
        }
        
        $app->boot();
        $config = $app->config();
        $this->assertInstanceOf(ReadOnlyConfig::class, $config);
        $this->assertSame('bar', $config->get('app.foo'));
    }
    
}

class DummyPlugin implements Plugin
{
    
    public WritableConfig $config;
    public bool           $configured = false;
    public bool           $registered = false;
    public bool           $booted     = false;
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        $this->config = $config;
        $this->configured = true;
    }
    
    public function register(Application $app) :void
    {
        $this->registered = true;
    }
    
    public function bootstrap(Application $app) :void
    {
        $this->booted = true;
    }
    
    public function alias() :string
    {
        return 'dummy_plugin';
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}