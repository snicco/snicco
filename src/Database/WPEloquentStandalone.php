<?php

declare(strict_types=1);

namespace Snicco\Database;

use Faker\Factory;
use RuntimeException;
use Illuminate\Support\Fluent;
use Illuminate\Container\Container;
use Faker\Generator as FakerGenerator;
use Illuminate\Support\Facades\Facade;
use Snicco\Database\Illuminate\WPModel;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Connectors\ConnectionFactory;
use Snicco\Database\Contracts\MysqliConnectionFactory;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;

final class WPEloquentStandalone
{
    
    /**
     * @var Container|Application
     */
    private $illuminate_container;
    
    /**
     * @var array
     */
    private $connection_configuration;
    
    /**
     * @var MysqliConnectionFactory
     */
    private $mysqli_factory;
    
    /**
     * @var bool
     */
    private $enable_global_facades;
    
    public function __construct(
        array $connection_configuration = [],
        bool $enable_global_facades = true,
        ?MysqliConnectionFactory $mysqli_factory = null
    ) {
        $this->illuminate_container = Container::getInstance();
        if ($this->illuminate_container->has('sniccowp_eloquent_bootstrapped')) {
            throw new RuntimeException(
                "EloquentStandalone can only be bootstrapped once because eloquent uses a global service locator.
                 If you are using this library in a distributed package you need to run it through a php-prefixer to avoid unintended code collision."
            );
        }
        
        if ($enable_global_facades
            && ! Facade::getFacadeApplication()
                 instanceof
                 IlluminateContainer) {
            Facade::setFacadeApplication($this->illuminate_container);
        }
        
        $this->enable_global_facades = $enable_global_facades;
        $this->connection_configuration = $connection_configuration;
        $this->mysqli_factory = $mysqli_factory ?? new MysqliFactoryUsingWpdb();
        $this->illuminate_container->instance('sniccowp_eloquent_bootstrapped', true);
    }
    
    public function withDatabaseFactories(string $model_namespace, string $factory_namespace, string $faker_locale = 'en_US')
    {
        $this->illuminate_container->singletonIf(
            FakerGenerator::class,
            function () use ($faker_locale) {
                $faker = Factory::create($faker_locale);
                $faker->unique(true);
                return $faker;
            }
        );
        EloquentFactory::guessFactoryNamesUsing(function (string $model) use ($factory_namespace) {
            $model = class_basename($model);
            return $factory_namespace.$model.'Factory';
        });
        EloquentFactory::guessModelNamesUsing(function ($factory) use ($model_namespace) {
            $model = class_basename($factory);
            return str_replace('Factory', '', $model_namespace.$model);
        });
        WPModel::$factory_namespace = $factory_namespace;
    }
    
    public function withEvents(Dispatcher $event_dispatcher)
    {
        $this->bindEventDispatcher($event_dispatcher);
    }
    
    public function bootstrap() :ConnectionResolverInterface
    {
        $this->bindConfig();
        $this->bindTransactionManager();
        
        $connection_resolver = $this->newConnectionResolver();
        Eloquent::setConnectionResolver($connection_resolver);
        
        if ($this->enable_global_facades) {
            $this->bindDBFacade($connection_resolver);
        }
        return $connection_resolver;
    }
    
    private function bindEventDispatcher(Dispatcher $event_dispatcher) :void
    {
        $this->illuminate_container->singleton('events', function () use ($event_dispatcher) {
            return $event_dispatcher;
        });
        Eloquent::setEventDispatcher($event_dispatcher);
    }
    
    private function bindDBFacade(ConnectionResolverInterface $connection_resolver) :void
    {
        $this->illuminate_container->singletonIf('db', function () use ($connection_resolver) {
            return $connection_resolver;
        });
    }
    
    private function newConnectionResolver() :ConnectionResolverInterface
    {
        $illuminate_database_manager = new DatabaseManager(
            $this->illuminate_container,
            new ConnectionFactory($this->illuminate_container)
        );
        
        return new WPConnectionResolver(
            $illuminate_database_manager,
            $this->mysqli_factory
        );
    }
    
    private function bindConfig()
    {
        if ($this->illuminate_container->has('config')) {
            $config = $this->illuminate_container->get('config');
            $config['database.connections'] = $this->connection_configuration;
        }
        else {
            // eloquent only needs some config element that works with array access.
            $this->illuminate_container->singleton('config', function () {
                $config = new Fluent();
                $config['database.connections'] = $this->connection_configuration;
                return $config;
            });
        }
    }
    
    private function bindTransactionManager()
    {
        $this->illuminate_container->singletonIf('db.transactions', function () {
            return new DatabaseTransactionsManager;
        });
    }
    
}