<?php

declare(strict_types=1);

namespace Tests\integration\Database;

use Snicco\Database\FakeDB;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Snicco\Database\WPConnectionResolver;
use Snicco\Database\Illuminate\MySqlSchemaBuilder;
use Snicco\Database\Contracts\BetterWPDbInterface;
use Snicco\Database\Contracts\ConnectionResolverInterface;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class WPConnectionResolverTest extends DatabaseTestCase
{
    
    protected bool $defer_boot = true;
    
    /** @test */
    public function testWithoutExtraConfigTheDefaultConnectionIsUsed()
    {
        
        $wpdb = $this->getResolver()->connection()->dbInstance();
        
        $this->assertDefaultConnection($wpdb);
        
    }
    
    private function getResolver(array $extra_connections = []) :WPConnectionResolver
    {
        
        $this->withAddedConfig('database.connections', $extra_connections)->boot();
        
        return $this->app->resolve(ConnectionResolverInterface::class);
        
    }
    
    /** @test */
    public function testGetDefaultConnectionName()
    {
        
        $name = $this->getResolver()->getDefaultConnection();
        
        $this->assertSame('wp_connection', $name);
        
    }
    
    /** @test */
    public function with_extra_connections_the_default_is_used_if_no_name_is_specified()
    {
        
        $wpdb = $this->getResolver(['secondary' => $this->secondDatabaseConfig()])->connection()
                     ->dbInstance();
        
        $this->assertDefaultConnection($wpdb);
        
    }
    
    /** @test */
    public function testExceptionResolvingInvalidSecondaryConnection()
    {
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid database connection [bogus] used.');
        
        $this->getResolver(['secondary' => $this->secondDatabaseConfig()])->connection('bogus');
        
    }
    
    /** @test */
    public function a_secondary_connection_can_be_resolved()
    {
        
        $c = $this->getResolver(['secondary' => $this->secondDatabaseConfig()])
                  ->connection('secondary');
        
        $this->assertNotDefaultConnection($c->dbInstance());
        $this->assertSame($_SERVER['SECONDARY_DB_NAME'], $c->dbInstance()->dbname);
        
    }
    
    /** @test */
    public function a_connection_with_a_fake_db_can_be_constructed()
    {
        
        $this->instance(BetterWPDbInterface::class, FakeDB::class);
        
        $resolver = $this->getResolver();
        $connection = $resolver->connection();
        
        $this->assertInstanceOf(FakeDB::class, $connection->dbInstance());
        
    }
    
    /** @test */
    public function testLaravelFacadesWork()
    {
        
        $this->boot();
        
        $connection = DB::connection();
        
        $this->assertDefaultConnection($connection->dbInstance());
        
    }
    
    /** @test */
    public function testLaravelFacadesWorkWithSecondConnection()
    {
        
        $this->withAddedConfig('database.connections', ['second' => $this->secondDatabaseConfig()])
             ->boot();
        
        $connection = DB::connection('second');
        
        $this->assertNotDefaultConnection($connection->dbInstance());
        
        $this->assertSame($_SERVER['SECONDARY_DB_NAME'], $connection->dbInstance()->dbname);
        
    }
    
    /** @test */
    public function once_a_connection_has_been_resolved_it_will_never_be_created_again()
    {
        
        $this->withAddedConfig('database.connections', ['second' => $this->secondDatabaseConfig()])
             ->boot();
        
        $connection1 = DB::connection('second');
        
        $this->assertNotDefaultConnection($connection1->dbInstance());
        $this->assertSame($_SERVER['SECONDARY_DB_NAME'], $connection1->dbInstance()->dbname);
        
        $connection2 = DB::connection('second');
        
        $this->assertNotDefaultConnection($connection2->dbInstance());
        $this->assertSame($_SERVER['SECONDARY_DB_NAME'], $connection2->dbInstance()->dbname);
        
        $this->assertSame($connection1, $connection2);
        
    }
    
    /** @test */
    public function via_the_schema_facade_the_schema_builder_can_be_resolved_with_other_connections()
    {
        
        $this->withAddedConfig('database.connections', ['second' => $this->secondDatabaseConfig()])
             ->boot();
        
        $builder = Schema::connection('second');
        $this->assertInstanceOf(MySqlSchemaBuilder::class, $builder);
        
        $db = $builder->getConnection()->dbInstance();
        $this->assertNotDefaultConnection($db);
        $this->assertSame($_SERVER['SECONDARY_DB_NAME'], $db->dbname);
        
    }
    
}