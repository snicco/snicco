<?php

declare(strict_types=1);

namespace Tests\Database\integration;

use Mockery as m;
use RuntimeException;
use mysqli_sql_exception;
use Codeception\TestCase\WPTestCase;
use Snicco\Database\MysqliConnection;
use Illuminate\Database\QueryException;
use Snicco\Database\Contracts\MysqliDriverInterface;

final class MysqliConnectionErrorHandlingTest extends WPTestCase
{
    
    /**
     * @var MysqliConnection
     */
    private $connection;
    
    /**
     * @var m\MockInterface|MysqliDriverInterface
     */
    private $mysqli_driver;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->mysqli_driver = m::mock(MysqliDriverInterface::class);
        global $wpdb;
        
        $this->connection = new MysqliConnection(
            $this->mysqli_driver,
            $wpdb->prefix
        );
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        m::close();
    }
    
    /** @test */
    public function errors_get_handled_for_inserts()
    {
        $this->mysqli_driver->shouldReceive('doStatement')->andThrow(
            new mysqli_sql_exception('this did not work.')
        );
        
        try {
            $this->connection->insert('foobar', ['foo' => 'bar']);
            $this->fail('No query exception thrown');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('this did not work', $e->getMessage());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame(['foo' => 'bar'], $e->getBindings());
        }
    }
    
    /** @test */
    public function errors_get_handles_for_updates()
    {
        $this->mysqli_driver->shouldReceive('doAffectingStatement')->andThrow(
            new mysqli_sql_exception('this did not work.')
        );
        
        try {
            $this->connection->update('foobar', ['foo' => 'bar']);
            $this->fail('No query exception thrown');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('this did not work', $e->getMessage());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame(['foo' => 'bar'], $e->getBindings());
        }
    }
    
    /** @test */
    public function errors_get_handled_for_deletes()
    {
        $this->mysqli_driver->shouldReceive('doAffectingStatement')->andThrow(
            new mysqli_sql_exception('this did not work.')
        );
        
        try {
            $this->connection->delete('foobar', ['foo' => 'bar']);
            $this->fail('No query exception thrown');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('this did not work', $e->getMessage());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame(['foo' => 'bar'], $e->getBindings());
        }
    }
    
    /** @test */
    public function errors_get_handled_for_unprepared_queries()
    {
        $this->mysqli_driver->shouldReceive('doUnprepared')->andThrow(
            new mysqli_sql_exception('oops')
        );
        
        try {
            $this->connection->unprepared('foobar');
            $this->fail('No query exception thrown');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('oops', $e->getMessage());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame([], $e->getBindings());
        }
    }
    
    /** @test */
    public function errors_get_handled_for_cursor_selects()
    {
        $this->mysqli_driver->shouldReceive('doCursorSelect')->andThrow(
            $mysqli_e = new mysqli_sql_exception()
        );
        
        try {
            $generator = $this->connection->cursor('foobar', ['foo' => 'bar']);
            
            foreach ($generator as $foo) {
                $this->fail('No Exception thrown');
            }
        } catch (QueryException $e) {
            $this->assertSame($mysqli_e, $e->getPrevious());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame(['foo' => 'bar'], $e->getBindings());
        }
    }
    
    /** @test */
    public function errors_get_handled_for_selects()
    {
        $this->mysqli_driver->shouldReceive('doSelect')->andThrow(
            $mysqli_e = new mysqli_sql_exception()
        );
        
        try {
            $this->connection->select('foobar', ['foo' => 'bar']);
            $this->fail("no exception thrown");
        } catch (QueryException $e) {
            $this->assertSame($mysqli_e, $e->getPrevious());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame(['foo' => 'bar'], $e->getBindings());
        }
    }
    
    /** @test */
    public function only_mysqli_exception_get_transformed_to_query_exceptions()
    {
        $this->mysqli_driver->shouldReceive('doSelect')->andThrow(
            new RuntimeException('fatal error')
        );
        
        try {
            $this->connection->select('foo');
            $this->fail('Wrong Exception type was handled');
        } catch (RuntimeException $exception) {
            $this->assertSame('fatal error', $exception->getMessage());
        }
    }
    
}