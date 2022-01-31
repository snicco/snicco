<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests;

use stdClass;
use Throwable;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Core\Bundle;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Directories;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Exception\MissingConfigKey;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\Component\Core\Tests\helpers\WriteTestConfig;
use Snicco\Component\Core\Tests\helpers\CreateTestContainer;

final class ApplicationBundlesTest extends TestCase
{
    
    use CreateTestContainer;
    use WriteTestConfig;
    
    private string $base_dir;
    private string $base_dir_with_bundles;
    
    protected function setUp() :void
    {
        parent::setUp();
        $_SERVER['_test'] = [];
        $this->base_dir = __DIR__.'/fixtures';
        $this->base_dir_with_bundles = $this->base_dir.'/base_dir_with_bundles';
        $this->cleanDirs([$this->base_dir.'/var/cache', $this->base_dir_with_bundles.'/var/cache']);
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        unset($_SERVER['_test']);
        $this->cleanDirs([$this->base_dir.'/var/cache', $this->base_dir_with_bundles.'/var/cache']);
    }
    
    /** @test */
    public function bundles_are_loaded_from_a_bundle_php_file()
    {
        $app1 = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $app1->boot();
        $this->assertFalse($app1->usesBundle('bundle_prod'));
        
        $app2 = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir_with_bundles)
        );
        
        $app2->boot();
        
        $this->assertTrue($app2->usesBundle('bundle_prod'));
    }
    
    /** @test */
    public function the_declared_bundle_environment_has_to_match_the_current_app_env()
    {
        $app1 = new Application(
            $this->createContainer(),
            Environment::dev(),
            Directories::fromDefaults($this->base_dir_with_bundles)
        );
        
        $app1->boot();
        // Bundle is not present because it's declared with env => prod
        $this->assertFalse($app1->usesBundle('bundle_prod'));
        
        $app2 = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir_with_bundles)
        );
        
        $app2->boot();
        // Bundle is now present because the environment matches.
        $this->assertTrue($app2->usesBundle('bundle_prod'));
    }
    
    /** @test */
    public function a_bundle_can_run_in_all_environments()
    {
        $app1 = new Application(
            $this->createContainer(),
            Environment::dev(),
            Directories::fromDefaults($this->base_dir_with_bundles)
        );
        
        $app1->boot();
        // Bundle is not present because it's declared with env => prod
        $this->assertFalse($app1->usesBundle('bundle_prod'));
        $this->assertTrue($app1->usesBundle('bundle_always'));
    }
    
    /** @test */
    public function test_exception_if_bundle_has_wrong_interface()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
        );
        
        $this->writeConfig($app, [
            'app' => [
            
            ],
            'bundles' => [
                stdClass::class => ['prod' => true],
            ],
        ]);
        
        try {
            $app->boot();
            $this->fail("no exception thrown");
        } catch (Throwable $e) {
            $this->assertStringContainsString("Snicco\\Component\\Core\\Bundle", $e->getMessage());
            $this->assertStringContainsString("stdClass", $e->getMessage());
        }
    }
    
    /** @test */
    public function plugins_are_loaded_in_the_order_they_are_declared_in_the_config()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir_with_bundles),
        );
        
        $app->boot();
        
        $this->assertTrue($app->config()->get('bundle1.configured'));
        $this->assertTrue($app['bundle1.registered']);
        $this->assertTrue($app['bundle1.booted']->val);
        
        $this->assertTrue($app->config()->get('bundle2.configured'));
        $this->assertTrue($app['bundle2.registered']);
        $this->assertTrue($app['bundle2.booted']->val);
    }
    
    /** @test */
    public function plugin_methods_are_called_in_the_correct_order()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir_with_bundles),
        );
        
        $app->boot();
        
        $this->assertTrue($app->config()['bundle_that_asserts_order.configured']);
        $this->assertTrue($app->di()['bundle_that_asserts_order.registered']);
        $this->assertTrue($app->di()['bundle_that_asserts_order.bootstrapped']->val);
    }
    
    /** @test */
    public function plugins_can_only_run_in_some_environments()
    {
        $app1 = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
        );
        
        $this->writeConfig($app1, [
            'bundles' => [
                BundleWithCustomEnv::class => ['all' => true],
            ],
        ]);
        
        $app1->boot();
        
        $this->assertFalse(
            isset($app1['custom_env_bundle_run']),
            "bundle was booted when it should not be."
        );
        
        $app2 = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
        );
        
        $this->writeConfig($app2, [
            'bundles' => [
                BundleWithCustomEnv::class => ['all' => true],
            ],
        ]);
        
        $_SERVER['_test']['custom_env_bundle_should_run'] = true;
        
        $app2->boot();
        
        $this->assertTrue(
            $app2['custom_env_bundle_run']->val,
            "bundle was not booted when it should be."
        );
    }
    
    /** @test */
    public function an_exception_is_thrown_if_two_plugins_use_the_same_alias()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                "2 bundles in your application share the same alias [duplicate_alias].\nAffected [%s]",
                implode(',', [BundleDuplicate1::class, BundleDuplicate2::class])
            )
        );
        
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
        );
        
        $this->writeConfig($app, [
            'bundles' => [
                BundleDuplicate1::class => ['all' => true],
                BundleDuplicate2::class => ['all' => true],
            ],
        ]);
        
        $app->boot();
    }
    
    /** @test */
    public function the_configure_method_will_not_called_if_the_configuration_is_cached()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
        );
        
        $this->writeConfig($app, [
            'bundles' => [
                BundleThatConfigures::class => ['all' => true],
            ],
        ]);
        
        $app->boot();
        
        // configure is not called.
        try {
            $app->config()->get('app.configured_value');
            $this->fail("Bundle::configure should not be called for a cached configuration.");
        } catch (MissingConfigKey $e) {
            $this->assertStringContainsString('app.configured_value', $e->getMessage());
        }
        
        // register was called
        $this->assertTrue($app['bundle_that_configures.registered']);
        // boot was called
        $this->assertTrue($app['bundle_that_configures.booted']->val);
        // plugin is used
        $this->assertTrue($app->usesBundle('bundle_that_configures'));
    }
    
}

class BundleThatConfigures implements Bundle
{
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        $config->set('app.configured_value', 'bar');
    }
    
    public function register(Application $app) :void
    {
        $app[$this->alias().'.registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $app[$this->alias().'.booted'] = $std;
    }
    
    public function bootstrap(Application $app) :void
    {
        $app[$this->alias().'.booted']->val = true;
    }
    
    public function alias() :string
    {
        return 'bundle_that_configures';
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}

class BundleWithCustomEnv implements Bundle
{
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        //
    }
    
    public function register(Application $app) :void
    {
        $std = new stdClass();
        $std->val = false;
        $app['custom_env_bundle_run'] = $std;
    }
    
    public function bootstrap(Application $app) :void
    {
        $app['custom_env_bundle_run']->val = true;
    }
    
    public function alias() :string
    {
        return 'custom_env_bundle';
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return isset($_SERVER['_test']['custom_env_bundle_should_run'])
               && $_SERVER['_test']['custom_env_bundle_should_run'] === true;
    }
    
}

class BundleDuplicate1 implements Bundle
{
    
    public function configure(WritableConfig $config, Application $app) :void
    {
    }
    
    public function register(Application $app) :void
    {
    }
    
    public function bootstrap(Application $app) :void
    {
    }
    
    public function alias() :string
    {
        return 'duplicate_alias';
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}

class BundleDuplicate2 implements Bundle
{
    
    public function configure(WritableConfig $config, Application $app) :void
    {
    }
    
    public function register(Application $app) :void
    {
    }
    
    public function bootstrap(Application $app) :void
    {
    }
    
    public function alias() :string
    {
        return 'duplicate_alias';
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}