<?php


    declare(strict_types = 1);

    namespace BetterWP\Database\Concerns;

    use Closure;
    use Illuminate\Database\Grammar;
    use Throwable;

    /**
     * @property WpdbInterface $wpdb
     * @property MySqlSchemaGrammar|Grammar $query_grammar
     */
    trait ManagesTransactions
    {

        /**
         * Execute a Closure within a transaction.
         *
         * @param  \Closure  $callback
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
                    // exception back out and let the developer handle an uncaught exceptions
                catch ( Throwable $e) {

                    $this->handleTransactionException($e, $currentAttempt, $attempts);

                    continue;

                }

                try {

                    $this->commit();

                }

                catch (Throwable $e) {

                    $this->handleCommitException($e, $currentAttempt, $attempts);

                    continue;
                }

                return $callbackResult;
            }
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
         * Rollback the active database transaction.
         *
         * @param  bool  $respect_savepoint
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

                    $this->wpdb->rollbackTransaction( $this->query_grammar->compileRollback() );

                }

                if ( $to_level > 0 ) {


                    $this->wpdb->rollbackTransaction(
                        $this->query_grammar->compileSavepointRollBack('trans'.($to_level))
                    );


                }


            }
            catch ( Throwable $e ) {

                $this->handleRollBackException($e);

            }

            $this->decreaseTransactionCount($to_level - 1 ?? null);


        }

        public function savepoint()
        {

            if ($this->transaction_count === 0) {

                try {

                    $this->wpdb->startTransaction();

                }

                catch ( mysqli_sql_exception $e) {

                    $this->handleBeginTransactionException($e);

                }

            }

            $this->wpdb->createSavepoint(

                $this->query_grammar->compileSavepoint('trans'.($this->transaction_count + 1))

            );

            $this->transaction_count++;


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
         * Get the active transaction_count.
         *
         * @return int
         */
        public function transactionLevel() : int
        {

            return $this->transaction_count;
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
            if ( $this->causedByLostConnection($e) && $this->tryReconnect() ) {


                $this->wpdb->startTransaction();

                return;

            }

            // If we can reconnect with wpdb or if we cant start the transaction a second time,
            // throw out the exception to the error handler
            $this->error_handler->handle(new QueryException('START TRANSACTION', [] , $e));


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

            // deadlock and attempts left.
            // MySql rolls everything back.
            if ($this->isConcurrencyError($e) && $this->transactionLevel() > 1 ) {

                $this->transaction_count = 0;

                throw $e;

            }

            $this->rollBack(max(1, $this->transaction_count));

            // deadlock, attempts left
            if ($this->isConcurrencyError($e) &&  $currentAttempt < $maxAttempts) {

                return;


            }

            $this->transaction_count = 0;
            $this->error_handler->handle(new QueryException('', [], $e));


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
        private function handleCommitException( Throwable $e, $currentAttempt, $maxAttempts)
        {

            if ($this->isConcurrencyError($e) && $currentAttempt < $maxAttempts) {

                return;

            }

            // Reset transaction count if we lost the connection
            if ( $this->causedByLostConnection($e) ) {

                $this->transaction_count = 0;

            }

            $this->error_handler->handle(new QueryException('COMMIT', [], $e));
        }

        /**
         * Handle an exception from a rollback.
         *
         * @param  Throwable|mysqli_sql_exception  $e
         *
         * @return void
         * @throws Throwable
         */
        private function handleRollBackException( Throwable $e )
        {

            if ($this->causedByLostConnection($e)) {

                $this->transaction_count = 0;

            }

            $this->error_handler->handle(new QueryException('ROLLBACK', [], $e));

        }

        private function tryReconnect () {

            return $this->wpdb->check_connection(false);

        }

    }