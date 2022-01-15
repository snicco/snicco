<?php

declare(strict_types=1);

namespace Tests\Core\unit\Configuration;

use RuntimeException;
use Snicco\Core\Application;
use Snicco\Core\Directories;
use Snicco\Core\Environment;
use Snicco\Core\Utils\CacheFile;
use Symfony\Component\Finder\Finder;
use Tests\Codeception\shared\UnitTest;
use Snicco\Core\Configuration\ConfigFactory;
use Snicco\Core\Configuration\WritableConfig;
use Tests\Codeception\shared\helpers\CreateContainer;

final class ConfigFactoryTest extends UnitTest
{
    
    use CreateContainer;
    
    private string $base_dir;
    private string $cache_dir;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->base_dir = dirname(__DIR__, 2).'/fixtures';
        $this->cache_dir = $this->base_dir.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'cache';
        $this->cleanDirs();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        $this->cleanDirs();
    }
    
    /** @test */
    public function a_configuration_instance_can_be_created()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $config = (new ConfigFactory())->create($app->directories()->configDir());
        
        $this->assertInstanceOf(WritableConfig::class, $config);
    }
    
    /** @test */
    public function all_files_in_the_config_directory_are_loaded_and_included_as_a_root_node_in_the_config()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $config = (new ConfigFactory())->create($app->directories()->configDir());
        
        $this->assertTrue($config->has('app'));
        $this->assertSame('bar', $config['app.foo']);
        
        $this->assertTrue($config->has('custom-config'));
        $this->assertSame('baz', $config['custom-config.foo']);
    }
    
    /** @test */
    public function the_configuration_can_be_cached()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $this->assertFalse(is_file($this->cache_dir.'/prod.config.json'));
        
        $config = (new ConfigFactory())->create(
            $app->directories()->configDir(),
            new CacheFile($app->directories()->cacheDir(), 'prod.config.json')
        );
        
        $this->assertTrue($config->has('app'));
        $this->assertSame('bar', $config['app.foo']);
        
        $this->assertTrue($config->has('custom-config'));
        $this->assertSame('baz', $config['custom-config.foo']);
        
        $this->assertTrue(
            is_file($this->cache_dir.'/prod.config.json'),
            "Config cache not created."
        );
    }
    
    /** @test */
    public function the_configuration_can_be_read_from_cache()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );
        
        $arr = [
            'app' => [
                'foo' => 'baz',
            ],
        ];
        
        $success = file_put_contents(
            $this->cache_dir.DIRECTORY_SEPARATOR.'prod.config.json',
            json_encode($arr)
        );
        
        if (false === $success) {
            throw new RuntimeException("cache not created in test setup");
        }
        
        $config = (new ConfigFactory())->create(
            $app->directories()->configDir(),
            new CacheFile($app->directories()->cacheDir(), 'prod.config.json')
        );
        
        $this->assertSame('baz', $config->get('app.foo'));
    }
    
    protected function cleanDirs() :void
    {
        $files = Finder::create()->in($this->cache_dir);
        foreach ($files as $file) {
            @unlink($file->getRealPath());
        }
    }
    
}