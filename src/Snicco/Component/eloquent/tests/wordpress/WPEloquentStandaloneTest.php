<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
use RuntimeException;
use Snicco\Component\Eloquent\Illuminate\MysqliConnection;
use Snicco\Component\Eloquent\Illuminate\WPConnectionResolver;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WPDBTestHelpers;
use Snicco\Component\Eloquent\WPEloquentStandalone;

/**
 * @internal
 */
final class WPEloquentStandaloneTest extends WPTestCase
{
    use WPDBTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Container::setInstance();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Eloquent::unsetEventDispatcher();
        Eloquent::unsetConnectionResolver();

        $this->withDatabaseExceptions(function (): void {
            global $wpdb;
            $wpdb->query('CREATE DATABASE IF NOT EXISTS sniccowp_2_testing');
        });
    }

    protected function tearDown(): void
    {
        $this->withDatabaseExceptions(function (): void {
            global $wpdb;
            $wpdb->query('DROP DATABASE IF EXISTS sniccowp_2_testing');
        });
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_cant_be_bootstrapped_twice(): void
    {
        (new WPEloquentStandalone())->bootstrap();
        $this->expectException(RuntimeException::class);
        (new WPEloquentStandalone())->bootstrap();
    }

    /**
     * @test
     */
    public function eloquent_is_booted(): void
    {
        $resolver = (new WPEloquentStandalone())->bootstrap();

        $this->assertInstanceOf(WPConnectionResolver::class, $resolver);

        $eloquent_resolver = Eloquent::getConnectionResolver();
        $this->assertInstanceOf(WPConnectionResolver::class, $eloquent_resolver);
        $this->assertSame($resolver, $eloquent_resolver);
    }

    /**
     * @test
     */
    public function test_can_be_booted_with_already_existing_config_key_in_container(): void
    {
        Container::getInstance()->instance('config', new Fluent());
        $resolver = (new WPEloquentStandalone())->bootstrap();

        $this->assertInstanceOf(WPConnectionResolver::class, $resolver);

        $eloquent_resolver = Eloquent::getConnectionResolver();
        $this->assertInstanceOf(WPConnectionResolver::class, $eloquent_resolver);
        $this->assertSame($resolver, $eloquent_resolver);
    }

    /**
     * @test
     */
    public function events_can_be_activated(): void
    {
        ($eloquent = new WPEloquentStandalone())
            ->bootstrap();

        $this->assertNull(Eloquent::getEventDispatcher());
        $eloquent->withEvents($d = new \Illuminate\Events\Dispatcher());

        $events = Eloquent::getEventDispatcher();
        $this->assertInstanceOf(Dispatcher::class, $events);
        $this->assertSame($events, Container::getInstance()['events']);
        $this->assertSame($events, $d);
    }

    /**
     * @test
     */
    public function the_db_facade_is_created(): void
    {
        (new WPEloquentStandalone())->bootstrap();
        $this->assertInstanceOf(MysqliConnection::class, DB::connection());

        $builder = DB::table('foo');
        $this->assertInstanceOf(Builder::class, $builder);
    }

    /**
     * @test
     */
    public function global_facades_can_be_disabled_is_created(): void
    {
        (new WPEloquentStandalone([], false))->bootstrap();

        $this->assertFalse(Container::getInstance()->has('db'));
    }

    /**
     * @test
     */
    public function the_schema_facade_works(): void
    {
        (new WPEloquentStandalone())->bootstrap();

        $schema = Schema::connection(null);

        $this->assertInstanceOf(MySqlBuilder::class, $schema);
    }

    /**
     * @test
     */
    public function the_default_connection_is_the_mysqli_connection_that_wordpress_always_creates(): void
    {
        (new WPEloquentStandalone())->bootstrap();
        $connection = DB::connection();
        $this->assertInstanceOf(MysqliConnection::class, $connection);
        $this->assertSame($connection, DB::connection());
    }

    /**
     * @test
     */
    public function different_connections_can_be_used_side_by_side(): void
    {
        (new WPEloquentStandalone($this->secondDatabaseConfig()))->bootstrap();

        $default = DB::connection();
        $this->assertInstanceOf(MySqlConnection::class, $default);

        $secondary = DB::connection('mysql2');
        $this->assertInstanceOf(MySqlConnection::class, $secondary);

        $this->assertNotSame($secondary->getName(), $default->getName());
    }

    /**
     * @test
     */
    public function the_transaction_manager_is_bound(): void
    {
        (new WPEloquentStandalone($this->secondDatabaseConfig()))->bootstrap();

        $this->assertInstanceOf(DatabaseTransactionsManager::class, Container::getInstance()['db.transactions']);
    }

    /**
     * @test
     */
    public function the_schema_builder_can_be_resolved_with_a_secondary_connection(): void
    {
        (new WPEloquentStandalone($this->secondDatabaseConfig()))->bootstrap();

        $schema = Schema::connection('mysql2');

        // The laravel Schema Builder.
        $this->assertInstanceOf(\Illuminate\Database\Schema\Builder::class, $schema);

        'mysql2' === $schema->getConnection()
            ->getName();
        'mysql' === $schema->getConnection()
            ->getConfig('driver');
        'sniccowp_testing_secondary' === $schema->getConnection()
            ->getConfig('database');
    }
}
