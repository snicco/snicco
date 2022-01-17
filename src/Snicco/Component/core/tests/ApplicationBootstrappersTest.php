<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Core\Bundle;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Directories;
use Snicco\Component\Core\Bootstrapper;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\Component\Core\Tests\helpers\WriteTestConfig;
use Snicco\Component\Core\Tests\helpers\CreateTestContainer;

final class ApplicationBootstrappersTest extends TestCase
{
    
    use CreateTestContainer;
    use WriteTestConfig;
    
    private string $base_dir;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->base_dir = __DIR__.'/fixtures';
        $this->cleanDirs([$this->base_dir.'/var/cache']);
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        $this->cleanDirs([$this->base_dir.'/var/cache']);
    }
    
    /** @test */
    public function bootstrappers_are_loaded_from_the_app_bootstrapper_key()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $this->writeConfig($app, [
            'app' => [
                'bootstrappers' => [
                    Bootstrap1::class,
                ],
            ],
        ]);
        
        $app->boot();
        
        $this->assertTrue($app['bootstrapper_1_registered']);
        $this->assertTrue($app['bootstrapper_1_booted']);
    }
    
    /** @test */
    public function bootstrappers_are_loaded_after_external_bundles()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $this->writeConfig($app, [
            'app' => [
                'bootstrappers' => [
                    Bootstrap2::class,
                ],
            ],
            'bundles' => [
                BundleInfo::class => ['all' => true],
            ],
        ]);
        
        $app->boot();
        
        $this->assertTrue($app->di()->get('bundle_info_registered'));
        $this->assertTrue($app->di()->get('bundle_info_booted'));
        
        $this->assertTrue($app->di()->get('bootstrapper_2_registered'));
        $this->assertTrue($app->di()->get('bootstrapper_2_booted'));
    }
    
}

class BundleInfo implements Bundle
{
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
    public function configure(WritableConfig $config, Application $app) :void
    {
    }
    
    public function register(Application $app) :void
    {
        $app['bundle_info_registered'] = true;
    }
    
    public function bootstrap(Application $app) :void
    {
        $app['bundle_info_booted'] = true;
    }
    
    public function alias() :string
    {
        return 'bundle_info';
    }
    
}

class Bootstrap1 implements Bootstrapper
{
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        $config->set('bootstrapper1_configured', true);
    }
    
    public function register(Application $app) :void
    {
        $app['bootstrapper_1_registered'] = true;
    }
    
    public function bootstrap(Application $app) :void
    {
        $app['bootstrapper_1_booted'] = true;
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}

class Bootstrap2 implements Bootstrapper
{
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        //
    }
    
    public function register(Application $app) :void
    {
        if ( ! $app->di()->has('bundle_info_registered')) {
            throw new RuntimeException('Bootstrapper registered before bundle');
        }
        $app['bootstrapper_2_registered'] = true;
    }
    
    public function bootstrap(Application $app) :void
    {
        if ( ! $app->di()->has('bundle_info_booted')) {
            throw new RuntimeException('Bootstrapper bootstrapped before bundle');
        }
        $app['bootstrapper_2_booted'] = true;
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}
