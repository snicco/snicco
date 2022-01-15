<?php

declare(strict_types=1);

namespace Tests\Database\integration;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Container\Container;
use Codeception\TestCase\WPTestCase;
use Snicco\Database\MysqliConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use Snicco\Database\WPEloquentStandalone;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;

final class MysqliConnectionConformsToInterfaceTest extends WPTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        
        Container::setInstance();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Model::unsetEventDispatcher();
        Model::unsetConnectionResolver();
        
        $wp_eloquent = new WPEloquentStandalone();
        $wp_eloquent->bootstrap();
    }
    
    /** @test */
    public function constructing_the_wp_connection_correctly_sets_up_all_collaborators()
    {
        global $wpdb;
        $wpdb->prefix = 'my_prefix';
        
        $connection = $this->getMysqliConnection();
        
        $query_grammar = $connection->getQueryGrammar();
        $this->assertInstanceOf(QueryGrammar::class, $query_grammar);
        $this->assertSame('my_prefix', $query_grammar->getTablePrefix());
        
        $schema_grammar = $connection->getSchemaGrammar();
        $this->assertInstanceOf(MySqlSchemaGrammar::class, $schema_grammar);
        $this->assertSame('my_prefix', $schema_grammar->getTablePrefix());
        
        $processor = $connection->getPostProcessor();
        $this->assertInstanceOf(MySqlProcessor::class, $processor);
        $this->assertSame(DB_NAME, $connection->getDatabaseName());
        $this->assertSame(MysqliConnection::CONNECTION_NAME, $connection->getName());
        
        $wpdb->prefix = 'wp_';
    }
    
    /** @test */
    public function the_query_builder_uses_the_correct_grammar_and_processor()
    {
        $wp_connection = $this->getMysqliConnection();
        
        $query_builder = $wp_connection->query();
        
        $this->assertInstanceOf(Builder::class, $query_builder);
        
        $this->assertSame($wp_connection->getPostProcessor(), $query_builder->processor);
        $this->assertSame($wp_connection->getQueryGrammar(), $query_builder->grammar);
    }
    
    /** @test */
    public function the_schema_builder_uses_the_correct_grammar_and_processor()
    {
        $wp_connection = $this->getMysqliConnection();
        
        $schema_builder = $wp_connection->getSchemaBuilder();
        
        $this->assertInstanceOf(MySqlBuilder::class, $schema_builder);
    }
    
    /** @test */
    public function the_connection_can_begin_a_query_against_a_query_builder_table()
    {
        $wp_connection = $this->getMysqliConnection();
        
        $query_builder = $wp_connection->table('wp_users', 'users');
        
        $this->assertInstanceOf(Builder::class, $query_builder);
        
        $this->assertSame('wp_users as users', $query_builder->from);
    }
    
    /** @test */
    public function bindings_get_prepared_correctly()
    {
        $result = $this->getMysqliConnection()->prepareBindings([
            true,
            false,
            'string',
            10,
            new DateTime('07.04.2021 15:00'),
        ]);
        
        $this->assertSame([
            1,
            0,
            'string',
            10,
            '2021-04-07 15:00:00',
        ], $result);
    }
    
    /** @test */
    public function test_get_config()
    {
        global $wpdb;
        $wpdb->prefix = 'my_prefix';
        
        $config = $this->getMysqliConnection()->getConfig();
        
        $this->assertEquals([
            'driver' => 'mysql',
            'name' => MysqliConnection::CONNECTION_NAME,
            'prefix' => 'my_prefix',
            'database' => DB_NAME,
            'password' => DB_PASSWORD,
            'username' => DB_USER,
            'charset' => DB_CHARSET,
            'collation' => null,
            'host' => DB_HOST,
        ], $config);
        
        $wpdb->prefix = 'wp_';
    }
    
    private function getMysqliConnection() :MysqliConnection
    {
        /** @var MysqliConnection $connection */
        $connection = DB::connection();
        return $connection;
    }
    
}