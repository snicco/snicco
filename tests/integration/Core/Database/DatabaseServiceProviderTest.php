<?php

declare(strict_types=1);

namespace Tests\integration\Core\Database;

use Faker\Generator;
use Snicco\Database\FakeDB;
use Snicco\Database\BetterWPDb;
use Illuminate\Support\Facades\DB;
use Snicco\Database\MysqliConnection;
use Snicco\Database\WPConnectionResolver;
use Snicco\Database\Illuminate\MySqlSchemaBuilder;
use Snicco\Database\Contracts\_ConnectionInterface;
use Snicco\Database\Contracts\MysqliDriverInterface;
use Illuminate\Database\ConnectionResolverInterface;

use const DB_USER;
use const DB_HOST;
use const DB_NAME;
use const DB_PASSWORD;

class DatabaseServiceProviderTest extends DatabaseTestCase
{
    
    /** @test */
    public function the_connection_resolver_is_bound_correctly()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            WPConnectionResolver::class,
            $this->app->resolve(ConnectionResolverInterface::class)
        );
    }
    
    /** @test */
    public function the_default_connection_is_set()
    {
        $this->bootApp();
        
        /** @var ConnectionResolverInterface $resolver */
        $resolver = $this->app->resolve(ConnectionResolverInterface::class);
        
        $this->assertSame('default_wp_connection', $resolver->getDefaultConnection());
    }
    
    /** @test */
    public function by_default_the_current_wpdb_instance_is_used()
    {
        $this->bootApp();
        
        /** @var ConnectionResolverInterface $resolver */
        $resolver = $this->app->resolve(ConnectionResolverInterface::class);
        $c = $resolver->connection();
        
        $this->assertInstanceOf(_ConnectionInterface::class, $c);
        
        $this->assertSame(DB_USER, $c->dbInstance()->dbuser);
        $this->assertSame(DB_HOST, $c->dbInstance()->dbhost);
        $this->assertSame(DB_NAME, $c->dbInstance()->dbname);
        $this->assertSame(DB_PASSWORD, $c->dbInstance()->dbpassword);
    }
    
    /** @test */
    public function the_wpdb_abstraction_can_be_changed()
    {
        $this->bootApp();
        $this->assertSame(BetterWPDb::class, $this->app->resolve(MysqliDriverInterface::class));
        
        $this->instance(MysqliDriverInterface::class, FakeDB::class);
        $this->assertSame(FakeDB::class, $this->app->resolve(MysqliDriverInterface::class));
    }
    
    /** @test */
    public function the_schema_builder_can_be_resolved_for_the_default_connection()
    {
        $this->bootApp();
        
        $b = $this->app->resolve(MySqlSchemaBuilder::class);
        $this->assertInstanceOf(MySqlSchemaBuilder::class, $b);
    }
    
    /** @test */
    public function the_connection_can_be_resolved_as_a_closure()
    {
        $this->bootApp();
        
        $connection = $this->app->resolve(_ConnectionInterface::class)();
        $this->assertInstanceOf(MysqliConnection::class, $connection);
    }
    
    /** @test */
    public function the_db_facade_works()
    {
        $this->bootApp();
        
        $connection = DB::connection();
        $this->assertInstanceOf(MysqliConnection::class, $connection);
    }
    
    /** @test */
    public function the_faker_instance_is_registered()
    {
        $this->bootApp();
        $this->assertInstanceOf(Generator::class, $this->app->resolve(Generator::class));
    }
    
}