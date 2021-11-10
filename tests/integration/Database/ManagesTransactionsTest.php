<?php

declare(strict_types=1);

namespace Tests\integration\Database;

use Exception;
use Throwable;
use Mockery as m;
use mysqli_sql_exception;
use Mockery\MockInterface;
use Snicco\Database\WPConnection;
use Snicco\Database\Exceptions\SqlException;
use Snicco\Database\Contracts\BetterWPDbInterface;

class ManagesTransactionsTest extends DatabaseTestCase
{
    
    private MockInterface $wpdb;
    
    /** @test */
    public function the_transaction_level_does_not_increment_when_an_exception_is_thrown()
    {
        
        $connection = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('check_connection')->andReturnFalse();
        
        $this->wpdb->shouldReceive('startTransaction')->once()
                   ->andThrow(mysqli_sql_exception::class);
        
        try {
            
            $connection->beginTransaction();
            
        } catch (SqlException $e) {
            
            $this->assertSame('START TRANSACTION', $e->getSql());
            
            $this->assertEquals(0, $connection->transactionLevel());
            
        }
        
    }
    
    /** @test */
    public function begin_transaction_reconnects_on_lost_connection_if_its_the_error_message_indicated_a_lost_connection()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()
                   ->andThrows(new mysqli_sql_exception('the server has gone away | TEST '));
        
        $this->wpdb->shouldReceive('startTransaction');
        $this->wpdb->shouldReceive('createSavePoint')->once()->with('SAVEPOINT trans1');
        
        $wp->beginTransaction();
        
        $this->assertSame(1, $wp->transactionLevel());
        
    }
    
    /**
     * MANUAL TRANSACTIONS
     */
    
    /** @test */
    public function if_an_exception_occurs_during_the_beginning_of_a_transaction_we_try_again_only_for_connection_errors()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()
                   ->andThrow(mysqli_sql_exception::class, 'Some random error | TEST ');
        
        try {
            $wp->beginTransaction();
        } catch (SqlException $e) {
            
            $this->assertEquals(0, $wp->transactionLevel());
            
        }
    }
    
    /** @test */
    public function if_we_fail_once_beginning_a_transaction_but_succeed_the_second_time_the_count_is_increased()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()
                   ->andThrows(new mysqli_sql_exception('server has gone away | TEST '));
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        
        try {
            
            $wp->beginTransaction();
            
            $this->assertEquals(1, $wp->transactionLevel());
            
        } catch (Throwable $e) {
            
            $this->fail('Unexpected Exception: '.$e->getMessage());
            
        }
        
    }
    
    /** @test */
    public function different_save_points_can_be_created_manually()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
        
        try {
            
            $wp->beginTransaction();
            
            $wp->savepoint();
            
            $this->assertEquals(2, $wp->transactionLevel());
            
        } catch (Throwable $e) {
            
            $this->fail('Unexpected Exception: '.$e->getMessage());
            
        }
        
    }
    
    /** @test */
    public function a_transaction_can_be_committed_manually()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        
        $this->wpdb->shouldReceive('commitTransaction')->once();
        
        try {
            
            $wp->beginTransaction();
            
            $this->assertEquals(1, $wp->transactionLevel());
            
            $wp->commit();
            
            $this->assertEquals(0, $wp->transactionLevel());
            
        } catch (Throwable $e) {
            
            $this->fail('Unexpected Exception: '.$e->getMessage());
            
        }
        
    }
    
    /** @test */
    public function a_transaction_can_be_committed_manually_with_several_custom_savepoints()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
        $this->wpdb->shouldReceive('commitTransaction')->once();
        
        try {
            
            $wp->beginTransaction();
            $wp->savepoint();
            
            $this->assertEquals(2, $wp->transactionLevel());
            
            $wp->commit();
            
            $this->assertEquals(0, $wp->transactionLevel());
            
        } catch (Throwable $e) {
            
            $this->fail('Unexpected Exception: '.$e->getMessage());
            
        }
        
    }
    
    /** @test */
    public function manual_rollbacks_restore_the_latest_save_point_by_default()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans3');
        $this->wpdb->shouldReceive('rollbackTransaction')->once()
                   ->with('ROLLBACK TO SAVEPOINT trans3');
        
        $wp->beginTransaction();
        $wp->savepoint();
        $wp->savepoint();
        
        // error happens here.
        
        $wp->rollBack();
        
        $this->assertEquals(2, $wp->transactionLevel());
        
    }
    
    /** @test */
    public function nothing_happens_if_an_invalid_level_is_provided_for_rollbacks()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
        $this->wpdb->shouldNotReceive('rollbackTransaction');
        
        $wp->beginTransaction();
        $wp->savepoint();
        
        $this->assertEquals(2, $wp->transactionLevel());
        
        $wp->rollBack(-4);
        $wp->rollBack(3);
        
    }
    
    /** @test */
    public function manual_rollbacks_to_custom_levels_work()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans3');
        $this->wpdb->shouldReceive('rollbackTransaction')->once()
                   ->with('ROLLBACK TO SAVEPOINT trans2');
        
        $wp->beginTransaction();
        $wp->savepoint();
        $wp->savepoint();
        
        // error happens here.
        
        $wp->rollBack(2);
        
        $this->assertEquals(1, $wp->transactionLevel());
        
    }
    
    /** @test */
    public function the_savepoint_methods_serves_as_an_alias_for_begin_transaction()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans3');
        $this->wpdb->shouldReceive('rollbackTransaction')->once()
                   ->with('ROLLBACK TO SAVEPOINT trans2');
        
        $wp->beginTransaction();
        $wp->savepoint();
        $wp->savepoint();
        
        // error happens here.
        
        $wp->rollBack(2);
        
        $this->assertEquals(1, $wp->transactionLevel());
        
    }
    
    /** @test */
    public function interacting_with_several_custom_savepoints_manually_works()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
        $this->wpdb->shouldNotReceive('createSavepoint')->with('SAVEPOINT trans3');
        
        $this->wpdb->shouldReceive('rollbackTransaction')->once()
                   ->with('ROLLBACK TO SAVEPOINT trans2');
        
        $wp->beginTransaction();
        
        $this->wpdb->shouldReceive('doStatement')->once()->andReturnTrue();
        $this->wpdb->shouldReceive('doStatement')->once()
                   ->andThrow(mysqli_sql_exception::class);
        $this->wpdb->shouldNotReceive('doAffectingStatement');
        
        try {
            
            $wp->insert('foobar', ['foo']);
            
            $wp->savepoint();
            
            // ERROR HERE
            $wp->insert('bizbar', ['foo']);
            
            $wp->savepoint();
            
            $wp->update('foobar', ['biz']);
            
        } catch (SqlException $e) {
            
            $wp->rollBack();
            
            $this->assertSame(1, $wp->transactionLevel());
            
        }
        
    }
    
    /** @test */
    public function the_transaction_can_be_rolled_back_completely_when_if_zero_is_provided()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans3');
        $this->wpdb->shouldReceive('rollbackTransaction')->once()->with("ROLLBACK");
        
        $wp->beginTransaction();
        $wp->savepoint();
        $wp->savepoint();
        
        // error happens here.
        
        $wp->rollBack(0);
        
        $this->assertEquals(0, $wp->transactionLevel());
        
    }
    
    /** @test */
    public function basic_automated_transactions_work_when_no_error_occurs()
    {
        
        $wp = $this->newWpTransactionConnection();
        $this->wpdb->shouldReceive('startTransaction')->once();
        $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('commitTransaction')->once();
        $this->wpdb->shouldReceive('doAffectingStatement')->once()->with('foo', ['bar'])
                   ->andReturn(3);
        
        $result = $wp->transaction(function (WpConnection $wp) {
            
            return $wp->update('foo', ['bar']);
            
        });
        
        $this->assertSame(3, $result);
        
    }
    
    /** @test */
    public function when_an_error_occurs_in_the_actual_query_we_try_again_until_it_works_or_no_attempts_are_left()
    {
        
        $wp = $this->newWpTransactionConnection();
        $this->wpdb->shouldReceive('startTransaction')->times(4);
        $this->wpdb->shouldReceive('createSavepoint')->times(4)->with('SAVEPOINT trans1');
        
        $this->wpdb->shouldReceive('rollbackTransaction')->times(3)
                   ->with('ROLLBACK TO SAVEPOINT trans1');
        
        $this->wpdb->shouldReceive('commitTransaction')->once();
        
        $this->wpdb->shouldReceive('doAffectingStatement')->once()->with('foo', ['bar'])
                   ->andReturn(3);
        
        $result = $wp->transaction(function (WpConnection $wp) {
            
            static $count = 0;
            
            if ($count != 3) {
                
                $count++;
                
                throw new mysqli_sql_exception('deadlock detected | TEST');
                
            }
            
            return $wp->update('foo', ['bar']);
            
        }, 4);
        
        $this->assertSame(3, $result);
        $this->assertSame(0, $wp->transactionLevel());
        
    }
    
    /**
     * TRANSACTION CLOSURES
     */
    
    /** @test */
    public function if_the_query_is_not_successful_after_the_max_attempt_we_throw_an_exception_all_the_way_out()
    {
        
        $wp = $this->newWpTransactionConnection();
        $this->wpdb->shouldReceive('startTransaction')->times(3);
        $this->wpdb->shouldReceive('createSavepoint')->times(3)->with('SAVEPOINT trans1');
        $this->wpdb->shouldReceive('rollbackTransaction')->times(3)
                   ->with('ROLLBACK TO SAVEPOINT trans1');
        
        $this->wpdb->shouldNotReceive('commitTransaction');
        
        $this->expectException(SqlException::class);
        
        $wp->transaction(function () {
            
            throw new mysqli_sql_exception('deadlock detected | TEST ');
            
        }, 3);
        
        $this->assertSame(0, $wp->transactionLevel());
    }
    
    /** @test */
    public function concurrency_errors_during_commits_are_retried()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once();
        $this->wpdb->shouldReceive('createSavepoint');
        
        $this->wpdb->shouldReceive('commitTransaction')->twice()
                   ->andThrows(new mysqli_sql_exception('deadlock detected'));
        $this->wpdb->shouldReceive('commitTransaction')->once();
        
        $count = $wp->transaction(function () {
            
            static $count = 0;
            
            $count++;
            
            return $count;
            
        }, 3);
        
        $this->assertSame(3, $count);
        $this->assertSame(0, $wp->transactionLevel());
        
    }
    
    /** @test */
    public function commit_errors_due_to_lost_connections_throw_an_exception()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once();
        $this->wpdb->shouldReceive('createSavepoint');
        
        $this->wpdb->shouldReceive('commitTransaction')->once()
                   ->andThrows(new mysqli_sql_exception('server has gone away | TEST'));
        
        $this->expectException(SqlException::class);
        
        $count = $wp->transaction(function () {
            
            static $count = 0;
            
            $count++;
            
            return $count;
            
        }, 3);
        
        $this->assertNull($count);
        $this->assertSame(0, $wp->transactionLevel());
        
    }
    
    /** @test */
    public function rollback_exceptions_reset_the_transaction_count_if_its_a_lost_connection()
    {
        
        $wp = $this->newWpTransactionConnection();
        
        $this->wpdb->shouldReceive('startTransaction')->once();
        $this->wpdb->shouldReceive('createSavepoint');
        
        $this->wpdb->shouldReceive('rollbackTransaction')
                   ->with('ROLLBACK TO SAVEPOINT trans1')
                   ->andThrow(new mysqli_sql_exception('server has gone away | TEST '));
        
        $this->expectException(SqlException::class);
        
        $wp->transaction(function () {
            
            throw new Exception();
            
        }, 3);
        
        $this->assertSame(0, $wp->transactionLevel());
        
    }
    
    protected function setUp() :void
    {
        
        $this->afterApplicationBooted(function () {
            
            $this->withFakeDb();
            
        });
        parent::setUp();
        $this->bootApp();
    }
    
    private function newWpTransactionConnection() :WPConnection
    {
        
        $this->wpdb = m::mock(BetterWPDbInterface::class);
        $this->wpdb->prefix = 'wp_';
        $this->wpdb->dbname = 'wp_testing';
        $this->wpdb->shouldReceive('check_connection')->andReturn(true)->byDefault();
        
        return new WpConnection($this->wpdb, 'wp_connection');
        
    }
    
}