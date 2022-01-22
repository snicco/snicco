<?php

declare(strict_types=1);

namespace Snicco\EloquentBundle;

use Illuminate\Database\ConnectionInterface;
use Snicco\Component\Eloquent\WPEloquentStandalone;
use Snicco\Component\Core\Contracts\ServiceProvider;
use Illuminate\Database\ConnectionResolverInterface;
use Snicco\Component\EventDispatcher\EventDispatcher;

class DatabaseServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bootEloquent();
    }
    
    function bootstrap() :void
    {
        //
    }
    
    private function bootEloquent()
    {
        $eloquent = new WPEloquentStandalone(
            $this->config->get('database.connections', []),
            $this->config->get('database.enable_global_facades', true)
        
        );
        $resolver = $eloquent->bootstrap();
        $this->container->singleton(
            ConnectionResolverInterface::class,
            function () use ($resolver) {
                return $resolver;
            }
        );
        $this->container->singleton(ConnectionInterface::class, function () {
            return $this->container[ConnectionResolverInterface::class]->connection();
        });
        
        if ($this->app->isRunningUnitTest()) {
            $eloquent->withDatabaseFactories(
                $this->config->get('database.model_namespace'),
                $this->config->get('database.factory_namespace')
            );
        }
        
        $eloquent->withEvents(
            new IlluminateEventDispatcherAdapter($this->container[EventDispatcher::class])
        );
    }
    
}