<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests;

use stdClass;
use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Core\Plugin;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Directories;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\Component\Core\Tests\helpers\CreateTestContainer;

final class ApplicationPluginsTest extends TestCase
{
    
    use CreateTestContainer;
    
    private string $base_dir;
    
    protected function setUp() :void
    {
        parent::setUp();
        $_SERVER['_plugins'] = [];
        $this->base_dir = __DIR__.'/fixtures';
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        unset($_SERVER['_plugins']);
    }
    
    /** @test */
    public function test_exception_if_plugin_not_instance_of_plugin()
    {
        $plugin1 = new Plugin1();
        $plugin2 = new stdClass();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Got: stdClass');
        
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir),
            [$plugin1, $plugin2]
        );
    }
    
    /** @test */
    public function plugins_are_loaded_in_the_order_they_are_passed_into_the_constructor()
    {
        $plugin1 = new Plugin1();
        $plugin2 = new Plugin2();
        
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir),
            [$plugin1, $plugin2]
        );
        
        $app->boot();
        
        $this->assertTrue($_SERVER['_plugins']['plugin1_configured']);
        $this->assertTrue($_SERVER['_plugins']['plugin1_registered']);
        $this->assertTrue($_SERVER['_plugins']['plugin1_booted']);
        
        $this->assertTrue($_SERVER['_plugins']['plugin2_configured']);
        $this->assertTrue($_SERVER['_plugins']['plugin2_registered']);
        $this->assertTrue($_SERVER['_plugins']['plugin2_booted']);
    }
    
    /** @test */
    public function plugin_methods_are_called_in_the_correct_order()
    {
        $plugin = new PluginAssertOrder();
        $this->assertFalse($plugin->booted);
        
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir),
            [$plugin]
        );
        
        $app->boot();
        
        $this->assertTrue($plugin->booted);
    }
    
    /** @test */
    public function plugins_can_only_run_in_some_environments()
    {
        $plugin = new PluginAssertOrder('dev');
        $this->assertFalse($plugin->booted);
        
        $app = new Application(
            $this->createContainer(),
            Environment::dev(),
            Directories::fromDefaults($this->base_dir),
            [$plugin]
        );
        
        $app->boot();
        $this->assertTrue($plugin->booted);
        
        $plugin = new PluginAssertOrder('prod');
        $this->assertFalse($plugin->booted);
        
        $app = new Application(
            $this->createContainer(),
            Environment::dev(),
            Directories::fromDefaults($this->base_dir),
            [$plugin]
        );
        
        $app->boot();
        $this->assertFalse($plugin->booted, "plugin booted in wrong environment.");
    }
    
    /** @test */
    public function an_exception_is_thrown_if_two_plugins_use_the_same_alias()
    {
        $plugin1 = new Plugin1('foobar');
        $plugin2 = new Plugin2('foobar');
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                "2 plugins in your application share the same alias [foobar].\nAffected [%s]",
                implode(',', [Plugin1::class, Plugin2::class])
            )
        );
        
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir),
            [$plugin1, $plugin2]
        );
    }
    
    /** @test */
    public function test_plugins_have_access_to_what_other_plugins_are_used()
    {
        $plugin1 = new PluginAssertOrder();
        $plugin2 = new PluginThatLogsUsedPlugins();
        
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir),
            [$plugin1, $plugin2]
        );
        
        $this->assertTrue($app->hasPlugin($plugin1->alias()));
        $this->assertTrue($app->hasPlugin($plugin2->alias()));
        
        $app->boot();
        
        $this->assertSame(true, $app->config()->get('_uses_plugin1'));
    }
    
}

class Plugin1 implements Plugin
{
    
    private ?string $alias;
    
    public function __construct(string $alias = null)
    {
        $this->alias = $alias;
    }
    
    public function alias() :string
    {
        return $this->alias ?? 'plugin1';
    }
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        if (isset($_SERVER['_plugins']['plugin2_configured'])) {
            throw new RuntimeException('plugin 2 configured first');
        }
        $_SERVER['_plugins']['plugin1_configured'] = true;
    }
    
    public function register(Application $app) :void
    {
        if (isset($_SERVER['_plugins']['plugin2_registered'])) {
            throw new RuntimeException('plugin 2 registered first');
        }
        $_SERVER['_plugins']['plugin1_registered'] = true;
    }
    
    public function bootstrap(Application $app) :void
    {
        if (isset($_SERVER['_plugins']['plugin2_booted'])) {
            throw new RuntimeException('plugin 2 booted first');
        }
        $_SERVER['_plugins']['plugin1_booted'] = true;
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}

class Plugin2 implements Plugin
{
    
    private ?string $alias;
    
    public function __construct(string $alias = null)
    {
        $this->alias = $alias;
    }
    
    public function alias() :string
    {
        return $this->alias ?? 'plugin2';
    }
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        if ( ! isset($_SERVER['_plugins']['plugin1_configured'])) {
            throw new RuntimeException('Plugin 1 not configured first.');
        }
        $_SERVER['_plugins']['plugin2_configured'] = true;
    }
    
    public function register(Application $app) :void
    {
        if ( ! isset($_SERVER['_plugins']['plugin1_registered'])) {
            throw new RuntimeException('Plugin 1 not registered first.');
        }
        $_SERVER['_plugins']['plugin2_registered'] = true;
    }
    
    public function bootstrap(Application $app) :void
    {
        if ( ! isset($_SERVER['_plugins']['plugin1_booted'])) {
            throw new RuntimeException('Plugin 1 not booted first.');
        }
        $_SERVER['_plugins']['plugin2_booted'] = true;
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}

class PluginAssertOrder implements Plugin
{
    
    public bool     $booted     = false;
    private bool    $configured = false;
    private bool    $registered = false;
    private ?string $env;
    
    public function __construct(string $run_in_env = null)
    {
        $this->env = $run_in_env;
    }
    
    public function alias() :string
    {
        return 'assert_order';
    }
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        $this->configured = true;
    }
    
    public function register(Application $app) :void
    {
        if ( ! $this->configured) {
            throw new RuntimeException('plugin registered before configured.');
        }
        $this->registered = true;
    }
    
    public function bootstrap(Application $app) :void
    {
        if ( ! $this->registered) {
            throw new RuntimeException('plugin booted before registered.');
        }
        if ( ! $this->configured) {
            throw new RuntimeException('plugin booted before registered.');
        }
        $this->booted = true;
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        if ( ! $this->env) {
            return true;
        }
        return $env->asString() === $this->env;
    }
    
}

class PluginThatLogsUsedPlugins implements Plugin
{
    
    public function alias() :string
    {
        return 'logging_plugin';
    }
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        $config->set('_uses_plugin1', $app->hasPlugin('assert_order'));
    }
    
    public function register(Application $app) :void
    {
        //
    }
    
    public function bootstrap(Application $app) :void
    {
        //
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}