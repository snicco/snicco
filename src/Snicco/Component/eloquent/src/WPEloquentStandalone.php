<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent;

use ArrayAccess;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Fluent;
use RuntimeException;
use Snicco\Component\Eloquent\Illuminate\WPConnectionResolver;
use Snicco\Component\Eloquent\Illuminate\WPModel;
use Snicco\Component\Eloquent\Mysqli\MysqliFactory;

use function class_exists;
use function rtrim;

final class WPEloquentStandalone
{
    private Container $illuminate_container;

    private array $connection_configuration;

    private bool $enable_global_facades;

    /**
     * @param mixed[] $connection_configuration
     */
    public function __construct(array $connection_configuration = [], bool $enable_global_facades = true)
    {
        $this->illuminate_container = Container::getInstance();

        if ($this->illuminate_container->has('snicco_eloquent_bootstrapped')) {
            throw new RuntimeException(
                'EloquentStandalone can only be bootstrapped once because eloquent uses a global service locator.
                 If you are using this library in a distributed package you need to run it through a php-scoper to avoid unintended code collision.'
            );
        }

        if ($enable_global_facades && ! Facade::getFacadeApplication() instanceof IlluminateContainer) {
            /** @psalm-suppress InvalidArgument */
            Facade::setFacadeApplication($this->illuminate_container);
        }

        $this->enable_global_facades = $enable_global_facades;
        $this->connection_configuration = $connection_configuration;
        $this->illuminate_container->instance('snicco_eloquent_bootstrapped', true);
    }

    public function withDatabaseFactories(
        string $model_namespace,
        string $factory_namespace,
        string $faker_locale = 'en_US'
    ): void {
        if (! class_exists(FakerGenerator::class)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                'Faker is not installed. Please try running composer require fakerphp/faker --dev'
            );
            // @codeCoverageIgnoreEnd
        }

        $this->illuminate_container->singletonIf(
            FakerGenerator::class,
            function () use ($faker_locale): FakerGenerator {
                $faker = Factory::create($faker_locale);
                $faker->unique(true);

                return $faker;
            }
        );

        EloquentFactory::guessFactoryNamesUsing(function (string $model) use ($factory_namespace): string {
            $model = class_basename($model);

            return rtrim($factory_namespace, '\\') . '\\' . $model . 'Factory';
        });

        EloquentFactory::guessModelNamesUsing(function (EloquentFactory $factory) use ($model_namespace): string {
            $model = class_basename($factory);

            return str_replace('Factory', '', rtrim($model_namespace, '\\') . '\\' . $model);
        });

        WPModel::$factory_namespace = $factory_namespace;
    }

    public function withEvents(Dispatcher $event_dispatcher): void
    {
        $this->bindEventDispatcher($event_dispatcher);
    }

    public function bootstrap(): WPConnectionResolver
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

    private function bindEventDispatcher(Dispatcher $event_dispatcher): void
    {
        $this->illuminate_container->singleton('events', fn (): Dispatcher => $event_dispatcher);
        Eloquent::setEventDispatcher($event_dispatcher);
    }

    private function bindConfig(): void
    {
        if ($this->illuminate_container->has('config')) {
            /** @var ArrayAccess $config */
            $config = $this->illuminate_container->get('config');
            $config['database.connections'] = $this->connection_configuration;
        } else {
            // eloquent only needs some config element that works with array access.
            $this->illuminate_container->singleton('config', function (): Fluent {
                $config = new Fluent();
                $config['database.connections'] = $this->connection_configuration;

                return $config;
            });
        }
    }

    private function bindTransactionManager(): void
    {
        $this->illuminate_container->singletonIf(
            'db.transactions',
            fn (): DatabaseTransactionsManager => new DatabaseTransactionsManager()
        );
    }

    private function newConnectionResolver(): WPConnectionResolver
    {
        /** @psalm-suppress InvalidArgument */
        $illuminate_database_manager = new DatabaseManager(
            $this->illuminate_container,
            new ConnectionFactory($this->illuminate_container)
        );

        return new WPConnectionResolver($illuminate_database_manager, new MysqliFactory());
    }

    private function bindDBFacade(ConnectionResolverInterface $connection_resolver): void
    {
        $this->illuminate_container->singletonIf('db', fn (): ConnectionResolverInterface => $connection_resolver);
    }
}
