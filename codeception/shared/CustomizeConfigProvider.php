<?php

declare(strict_types=1);

namespace Tests\Codeception\shared;

use Snicco\Application\Config;
use Snicco\Shared\ContainerAdapter;
use Snicco\Contracts\ServiceProvider;

final class CustomizeConfigProvider extends ServiceProvider
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