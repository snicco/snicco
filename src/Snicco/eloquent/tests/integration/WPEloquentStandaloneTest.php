<?php

declare(strict_types=1);

namespace Tests\Database\integration;

use wpdb;
use stdClass;
use RuntimeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Container\Container;
use Codeception\TestCase\WPTestCase;
use Snicco\Database\MysqliConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\MySqlConnection;
use Snicco\Database\WPConnectionResolver;
use Snicco\Database\WPEloquentStandalone;
use Illuminate\Contracts\Events\Dispatcher;
use Tests\Database\helpers\WPDBTestHelpers;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\DatabaseTransactionsManager;

final class WPEloquentStandaloneTest extends WPTestCase
{
    
    use WPDBTestHelpers;
    
    protected function setUp() :void
    {
        parent::setUp();
        Container::setInstance();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Eloquent::unsetEventDispatcher();
        Eloquent::unsetConnectionResolver();
    }
    
    /** @test */
    public function test_cant_be_bootstrapped_twice()
    {
        (new WPEloquentStandalone())->bootstrap();
        $this->expectException(RuntimeException::class);
        (new WPEloquentStandalone())->bootstrap();
    }
    
    /** @test */
    public function eloquent_is_booted()
    {
        $resolver = (new WPEloquentStandalone())->bootstrap();
        
        $this->assertInstanceOf(WPConnectionResolver::class, $resolver);
        
        $eloquent_resolver = Eloquent::getConnectionResolver();
        $this->assertInstanceOf(WPConnectionResolver::class, $eloquent_resolver);
        $this->assertSame($resolver, $eloquent_resolver);
    }
    
    /** @test */
    public function events_can_be_activated()
    {
        ($eloquent = new WPEloquentStandalone())->bootstrap();
        
        $this->assertNull(Eloquent::getEventDispatcher());
        $eloquent->withEvents($d = new \Illuminate\Events\Dispatcher());
        
        $events = Eloquent::getEventDispatcher();
        $this->assertInstanceOf(Dispatcher::class, $events);
        $this->assertSame($events, Container::getInstance()['events']);
        $this->assertSame($events, $d);
    }
    
    /** @test */
    public function the_db_facade_is_created()
    {
        (new WPEloquentStandalone())->bootstrap();
        $this->assertInstanceOf(MysqliConnection::class, DB::connection());
        
        $builder = DB::table('foo');
        $this->assertInstanceOf(Builder::class, $builder);
    }
    
    /** @test */
    public function global_facades_can_be_disabled_is_created()
    {
        (new WPEloquentStandalone([], false))->bootstrap();
        
        $this->assertFalse(Container::getInstance()->has('db'));
    }
    
    /** @test */
    public function the_schema_facade_works()
    {
        (new WPEloquentStandalone())->bootstrap();
        
        $schema = Schema::connection(null);
        
        $this->assertInstanceOf(MySqlBuilder::class, $schema);
    }
    
    /** @test */
    public function the_default_connection_is_the_mysqli_connection_that_wordpress_always_creates()
    {
        (new WPEloquentStandalone())->bootstrap();
        $connection = DB::connection();
        $this->assertInstanceOf(MysqliConnection::class, $connection);
        $this->assertSame($connection, DB::connection());
    }
    
    /** @test */
    public function secondary_connections_can_be_used()
    {
        (new WPEloquentStandalone($this->secondDatabaseConfig()))->bootstrap();
        
        $connection = DB::connection('mysql2');
        
        $this->assertInstanceOf(MySqlConnection::class, $connection);
        $this->assertSame($_SERVER['SECONDARY_DB_NAME'], $connection->getDatabaseName());
    }
    
    /** @test */
    public function different_connections_can_be_used_side_by_side()
    {
        $default_connection_blog_name = get_bloginfo('name');
        
        $wpdb = new wpdb(
            $_SERVER['SECONDARY_DB_USER'],
            $_SERVER['SECONDARY_DB_PASSWORD'],
            $_SERVER['SECONDARY_DB_NAME'],
            $_SERVER['SECONDARY_DB_HOST']
        );
        
        $wpdb->query(
            "UPDATE wp_options SET option_value = 'SECONDARY_BLOG_NAME' where option_name = 'blogname'"
        );
        // We are inside a wp-browser transaction here.
        $wpdb->query('COMMIT');
        
        (new WPEloquentStandalone($this->secondDatabaseConfig()))->bootstrap();
        
        $connection = DB::connection('mysql2');
        
        $db_value = $connection->table('options')
                               ->where('option_name', 'blogname')
                               ->select(['option_value', 'option_name'])
                               ->first();
        
        $this->assertNotSame($default_connection_blog_name, $db_value->option_value);
        $this->assertSame('SECONDARY_BLOG_NAME', $db_value->option_value);
        
        $record = DB::connection()->table('options')
                    ->where('option_name', 'blogname')
                    ->select(['option_value', 'option_name'])
                    ->first();
        
        $this->assertInstanceOf(stdClass::class, $record);
        $this->assertSame($default_connection_blog_name, $record->option_value);
    }
    
    /** @test */
    public function the_transaction_manager_is_bound()
    {
        (new WPEloquentStandalone($this->secondDatabaseConfig()))->bootstrap();
        
        $this->assertInstanceOf(
            DatabaseTransactionsManager::class,
            Container::getInstance()['db.transactions']
        );
    }
    
    /** @test */
    public function the_schema_builder_can_be_resolved_with_a_secondary_connection()
    {
        (new WPEloquentStandalone($this->secondDatabaseConfig()))->bootstrap();
        
        $schema = Schema::connection('mysql2');
        
        // The laravel Schema Builder.
        $this->assertInstanceOf(\Illuminate\Database\Schema\Builder::class, $schema);
        
        $schema->getConnection()->getName() === 'mysql2';
        $schema->getConnection()->getConfig('driver') === 'mysql';
        $schema->getConnection()->getConfig('database') === 'sniccowp_testing_secondary';
    }
    
}