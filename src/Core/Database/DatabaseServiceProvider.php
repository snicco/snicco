<?php

declare(strict_types=1);

namespace Snicco\Core\Database;

use Snicco\Contracts\ServiceProvider;
use Snicco\Database\WPEloquentStandalone;

use const DB_NAME;
use const DB_HOST;
use const DB_USER;
use const DB_PASSWORD;

class DatabaseServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        
        $eloquent = new WPEloquentStandalone($this->config->get('database.connections'));
        $eloquent->bootstrap();
        if ($this->app->isRunningUnitTest()) {
            $eloquent->withDatabaseFactories(
                $this->config->get('database.model_namespace'),
                $this->config->get('database.factory_namespace')
            );
        }
    }
    
    function bootstrap() :void
    {
        //
    }
    
    private function bindConfig()
    {
        $this->config->extend('database.connections', [
            'default_wp_connection' => [
                'username' => DB_USER,
                'database' => DB_NAME,
                'password' => DB_PASSWORD,
                'host' => DB_HOST,
            ],
        ]);
    }
    
    private function bindSchemaBuilder()
    {
        $this->container->singleton(MySqlSchemaBuilder::class, function () {
            return new MySqlSchemaBuilder($this->resolveConnection());
        });
    }
    
    private function resolveConnection(string $name = null)
    {
        return $this->container->make(ConnectionResolverInterface::class)->connection($name);
    }
    
}