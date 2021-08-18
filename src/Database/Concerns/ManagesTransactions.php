<?php

declare(strict_types=1);

namespace Snicco\Database\Concerns;

use Closure;
use Throwable;
use Snicco\Support\Str;
use mysqli_sql_exception;
use Illuminate\Database\Grammar;
use Illuminate\Database\QueryException;
use Snicco\Database\Contracts\BetterWPDbInterface;
use Snicco\Database\Illuminate\MySqlSchemaGrammar;

/**
 * @property BetterWPDbInterface $wpdb
 * @property MySqlSchemaGrammar|Grammar $query_grammar
 */
trait ManagesTransactions
{
    
    /** @var int */
    protected $transaction_count = 0;
    
    /**
     * Execute a Closure within a transaction.
     *
     * @param  Closure  $callback
     * @param  int  $attempts
     *
     * @return mixed
     * @throws Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        
        $this->transaction_count = 0;
        
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            
            $this->savepoint();
            
            // We'll simply execute the given callback within a try / catch block and if we
            // catch any exception we can rollback this transaction so that none of this
            // gets actually persisted to a database or stored in a permanent fashion.
            try {
                
                $callbackResult = $callback($this);
                
            }
                
                // If we catch an exception we'll rollback this transaction and try again if we
                // are not out of attempts. If we are out of attempts we will just throw the
                // exception back out and let the developer handlean uncaught exceptions.
            catch (Throwable $e) {
                
                $this->handleTransactionException($e, $currentAttempt, $attempts);
                
                // We could handlethe exception and are ready to try again.
                continue;
                
            }
            
            try {
                
                $this->commit();
                
            } catch (Throwable $e) {
                
                $this->handleCommitException($e, $currentAttempt, $attempts);
                
                continue;
            }
            
            return $callbackResult;
        }
    }
    
    public function savepoint()
    {
        
        if ($this->transaction_count === 0) {
            
            try {
                
                $this->wpdb->startTransaction();
                
            } catch (mysqli_sql_exception $e) {
                
                $this->handleBeginTransactionException($e);
                
            }
            
        }
        
        $this->wpdb->createSavepoint(
            
            $this->query_grammar->compileSavepoint('trans'.($this->transaction_count + 1))
        
        );
        
        $this->transaction_count++;
        
    }
    
    /**
     * Handle an exception from a transaction beginning.
     *
     * @param  mysqli_sql_exception  $e
     *
     * @return void
     * @throws QueryException
     */
    private function handleBeginTransactionException(mysqli_sql_exception $e)
    {
        
        // If the caused by lost connection, reconnect again and redo transaction
        // wpdb automatically tries to reconnect if we lost the connection.
        if ($this->causedByLostConnection($e) && $this->tryReconnect()) {
            
            $this->wpdb->startTransaction();
            
            return;
            
        }
        
        // If we can reconnect with wpdb or if we cant start the transaction a second time,
        // throw out the exception to the error driver.
        throw new QueryException('START TRANSACTION', [], $e);
        
    }
    
    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  Throwable  $e
     *
     * @return bool
     */
    private function causedByLostConnection(Throwable $e)
    {
        
        $message = $e->getMessage();
        
        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'ORA-03114',
            'Packets out of order. Expected',
            'Adaptive Server connection failed',
            'Communication link failure',
            'connection is no longer usable',
            'Login timeout expired',
            'SQLSTATE[HY000] [2002] WordpressConnection refused',
            'running with the --read-only option so it cannot execute this statement',
            'The connection is broken and recovery is not possible. The connection is marked by the client driver as unrecoverable. No attempt was made to restore the connection.',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Try again',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Name or service not known',
            'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error: EOF detected',
            'SQLSTATE[HY000] [2002] WordpressConnection timed out',
            'SSL: WordpressConnection timed out',
            'SQLSTATE[HY000]: General error: 1105 The last transaction was aborted due to Seamless Scaling. Please retry.',
            'Temporary failure in name resolution',
            'SSL: Broken pipe',
        ]);
    }
    
    private function tryReconnect()
    {
        
        /** @see \wpdb::check_connection() */
        return $this->wpdb->check_connection(false);
        
    }
    
    /**
     * Handle an exception encountered when running a transacted statement.
     *
     * @param  Throwable|mysqli_sql_exception  $e
     * @param  int  $currentAttempt
     * @param  int  $maxAttempts
     *
     * @return void
     * @throws Throwable
     */
    private function handleTransactionException(Throwable $e, $currentAttempt, $maxAttempts)
    {
        
        // deadlock and already transaction started.
        // MySql rolls everything back.
        if ($this->isConcurrencyError($e) && $this->transactionLevel() > 1) {
            
            $this->transaction_count = 0;
            
            throw $e;
            
        }
        
        $this->rollBack(max(1, $this->transaction_count));
        
        // deadlock, attempts left
        if ($this->isConcurrencyError($e) && $currentAttempt < $maxAttempts) {
            
            return;
            
        }
        
        $this->transaction_count = 0;
        throw new QueryException('', [], $e);
        
    }
    
    private function isConcurrencyError(Throwable $e) :bool
    {
        
        $message = $e->getMessage();
        
        return Str::contains($message, [
            'Deadlock found when trying to get lock',
            'deadlock detected',
            'The database file is locked',
            'database is locked',
            'database table is locked',
            'A table in the database is locked',
            'has been chosen as the deadlock victim',
            'Lock wait timeout exceeded; try restarting transaction',
            'WSREP detected deadlock/conflict and aborted the transaction. Try restarting the transaction',
        ]);
    }
    
    /**
     * Get the active transaction_count.
     *
     * @return int
     */
    public function transactionLevel() :int
    {
        
        return $this->transaction_count;
    }
    
    /**
     * Rollback the active database transaction.
     *
     * @param  null  $to_level
     *
     * @return void
     * @throws Throwable
     */
    public function rollBack($to_level = null)
    {
        
        $to_level = $to_level ?? $this->transaction_count;
        
        if ($to_level < 0 || $to_level > $this->transaction_count) {
            
            return;
            
        }
        
        try {
            
            if ($to_level === 0) {
                
                $this->wpdb->rollbackTransaction($this->query_grammar->compileRollback());
                
            }
            
            if ($to_level > 0) {
                
                $this->wpdb->rollbackTransaction(
                    $this->query_grammar->compileSavepointRollBack('trans'.($to_level))
                );
                
            }
            
        } catch (Throwable $e) {
            
            $this->handleRollBackException($e);
            
        }
        
        $this->decreaseTransactionCount($to_level - 1 ?? null);
        
    }
    
    /**
     * Handle an exception from a rollback.
     *
     * @param  Throwable|mysqli_sql_exception  $e
     *
     * @return void
     * @throws Throwable
     */
    private function handleRollBackException(Throwable $e)
    {
        
        if ($this->causedByLostConnection($e)) {
            
            $this->transaction_count = 0;
            
        }
        
        throw new QueryException('ROLLBACK', [], $e);
        
    }
    
    private function decreaseTransactionCount($to_level = null)
    {
        
        $this->transaction_count--;
        
        if ($to_level) {
            
            $this->transaction_count = $to_level;
            
        }
        
        if ($this->transaction_count < 0) {
            
            $this->transaction_count = 0;
            
        }
        
    }
    
    /**
     * Commit the active database transaction.
     *
     * @return void
     * @throws Throwable
     */
    public function commit()
    {
        
        $this->wpdb->commitTransaction();
        
        // If successfully reset the transaction count.
        $this->transaction_count = 0;
        
    }
    
    /**
     * Handle an exception encountered when committing a transaction.
     *
     * @param  Throwable|mysqli_sql_exception  $e
     * @param  int  $currentAttempt
     * @param  int  $maxAttempts
     *
     * @return void
     * @throws Throwable
     */
    private function handleCommitException(Throwable $e, $currentAttempt, $maxAttempts)
    {
        
        if ($this->isConcurrencyError($e) && $currentAttempt < $maxAttempts) {
            
            return;
            
        }
        
        // Reset transaction count if we lost the connection
        if ($this->causedByLostConnection($e)) {
            
            $this->transaction_count = 0;
            
        }
        
        throw new QueryException('COMMIT', [], $e);
    }
    
    /**
     * Start a new database transaction.
     *
     * @return void
     * @throws Throwable
     */
    public function beginTransaction()
    {
        
        // In case we have some global state left, because Wordpress...
        $this->transaction_count = 0;
        
        $this->savepoint();
        
    }
    
}