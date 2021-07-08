<?php


    declare(strict_types = 1);


    namespace BetterWP\Database;

    use BetterWP\Database\Concerns\LogsQueries;
    use BetterWP\Database\Concerns\ManagesTransactions;
    use BetterWP\Database\Contracts\WPConnectionInterface;
    use BetterWP\Database\Contracts\BetterWPDbInterface;
    use BetterWP\Database\Illuminate\MySqlQueryGrammar;
    use BetterWP\Database\Illuminate\MySqlSchemaBuilder;
    use BetterWP\Database\Illuminate\MySqlSchemaGrammar;
    use BetterWpdb\PdoAdapter;
    use Closure;
    use DateTime;
    use Generator;
    use Illuminate\Database\Grammar;
    use Illuminate\Database\Query\Builder as QueryBuilder;
    use Illuminate\Database\Query\Expression;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\QueryException;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use mysqli_result;
    use mysqli_sql_exception;

    class WPConnection implements WPConnectionInterface
    {

        use ManagesTransactions;
        use LogsQueries;

        /**
         * @var BetterWPDbInterface
         */
        private $wpdb;

        /**
         * @var string
         */
        private $db_name;

        /**
         * @var string
         */
        private $table_prefix;

        /** @var MySqlQueryGrammar */
        private $query_grammar;

        /** @var MySqlSchemaGrammar */
        private $schema_grammar;

        /**
         * @var MySqlProcessor
         */
        private $post_processor;

        /**
         * Indicates if the connection is in a "dry run".
         *
         * @var bool
         */
        protected $pretending = false;

        private $logging_queries = false;


        public function __construct(BetterWPDbInterface $wpdb)
        {

            $this->wpdb = $wpdb;
            $this->db_name = $wpdb->dbname;
            $this->table_prefix = $wpdb->prefix;
            $this->query_grammar = $this->withTablePrefix(new MySqlQueryGrammar());
            $this->schema_grammar = $this->withTablePrefix(new MySqlSchemaGrammar());
            $this->post_processor = new MySqlProcessor();

        }

        public function dbInstance() : BetterWPDbInterface
        {

            return $this->wpdb;
        }

        /**
         * Get the query grammar used by the connection.
         * The QueryGrammar is used to "translate" the QueryBuilder instance into raw
         * SQL
         *
         * @return \BetterWpdb\ExtendsIlluminate\MySqlQueryGrammar;
         */
        public function getQueryGrammar() : MySqlQueryGrammar
        {

            return $this->query_grammar;
        }


        /**
         * Get the query post processor used by the connection.
         *
         * @return MySqlProcessor
         */
        public function getPostProcessor() : MySqlProcessor
        {

            return $this->post_processor;
        }


        /**
         * Get the schema grammar used by the connection.
         *
         * @return \BetterWpdb\ExtendsIlluminate\MySqlSchemaGrammar
         */
        public function getSchemaGrammar() : MySqlSchemaGrammar
        {

            return $this->schema_grammar;
        }

        /**
         * Get a schema builder instance for the connection.
         *
         * @return \BetterWpdb\ExtendsIlluminate\MySqlSchemaBuilder
         */
        public function getSchemaBuilder() : MySqlSchemaBuilder
        {

            return new MySqlSchemaBuilder($this);

        }


        /**
         * Set the table prefix and return the grammar for
         * a QueryBuilder or SchemaBuilder
         *
         * @param  Grammar  $grammar
         *
         * @return Grammar
         */
        private function withTablePrefix(Grammar $grammar) : Grammar
        {

            $grammar->setTablePrefix($this->table_prefix);

            return $grammar;

        }



        /*
        |
        |
        |--------------------------------------------------------------------------
        | Sanitizing and preparing the data to be passed into wpdb
        |--------------------------------------------------------------------------
        |
        |
        | We have to do all the sanitization ourselves and cant rely on the wpdb
        | class.
        |
        |
        |
        */

        /**
         * Prepare the query bindings for execution.
         *
         * @param  array  $bindings
         *
         * @return array
         */
        public function prepareBindings(array $bindings) : array
        {

            foreach ($bindings as $key => $binding) {

                // Micro-optimization: check for scalar values before instances
                if (is_bool($binding)) {
                    $bindings[$key] = intval($binding);
                }
                elseif (is_scalar($binding)) {

                    continue;

                }
                elseif ($binding instanceof DateTime) {

                    // We need to transform all instances of the DateTime class into an actual
                    // date string. Each query grammar maintains its own date string format
                    // so we'll just ask the grammar for the format to get from the date.
                    $bindings[$key] = $binding->format($this->getQueryGrammar()
                                                            ->getDateFormat());

                }
            }

            return $bindings;

        }



        /*
        |
        |
        |--------------------------------------------------------------------------
        | Query methods defined in the ConnectionInterface
        |--------------------------------------------------------------------------
        |
        |
        | Here is where we have to do most of the work since we need to
        | process all queries through the active wpdb instance in order to not
        | open a new db connection.
        |
        |
        */

        /**
         * Begin a fluent query against a database table.
         *
         * @param  Closure| QueryBuilder |string  $table
         * @param  string|null  $as
         *
         * @return QueryBuilder
         */
        public function table($table, $as = null) : QueryBuilder
        {

            return $this->query()->from($table, $as);

        }


        /**
         * Get a new query builder instance.
         *
         * @return QueryBuilder
         */
        public function query() : QueryBuilder
        {

            return new QueryBuilder(
                $this, $this->getQueryGrammar(), $this->getPostProcessor()
            );

        }

        /**
         * Run a select statement and return a single result.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  bool  $useReadPdo  , can be ignored.
         *
         * @return mixed
         */
        public function selectOne($query, $bindings = [], $useReadPdo = true)
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return [];
                }

                $result = $this->wpdb->doSelect($query, $bindings);

                return array_shift($result);

            }

            );


        }

        /**
         * Run a select statement against the database and return a set of rows
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  bool  $useReadPdo
         *
         * @return array
         * @throws QueryException
         */
        public function select($query, $bindings = [], $useReadPdo = true)
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return [];
                }

                try {

                    return $this->wpdb->doSelect($query, $bindings);

                }

                catch (mysqli_sql_exception $e) {

                    throw new QueryException($query, $bindings, $e);

                }

            });

        }

        // Method is used by eloquent for pdo calls.
        public function selectFromWriteConnection($query, $bindings = []) : array
        {

            return $this->select($query, $bindings, false);

        }

        /**
         * Run an insert statement against the database.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return bool
         * @throws QueryException
         */
        public function insert($query, $bindings = [])
        {

            return $this->statement($query, $bindings);

        }

        /**
         * Run an update statement against the database.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return int
         * @throws QueryException
         */
        public function update($query, $bindings = [])
        {

            return $this->affectingStatement($query, $bindings);
        }

        /**
         * Run a delete statement against the database.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return int
         * @throws QueryException
         */
        public function delete($query, $bindings = [])
        {

            return $this->affectingStatement($query, $bindings);

        }

        /**
         * Execute an SQL statement and return the boolean result.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return bool
         * @throws QueryException
         */
        public function statement($query, $bindings = [])
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return true;
                }

                try {

                    return $this->wpdb->doStatement($query, $bindings);

                }

                catch (mysqli_sql_exception $e) {

                    throw new QueryException($query, $bindings, $e);

                }


            });


        }

        /**
         * Run an SQL statement and get the number of rows affected.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return int
         * @throws QueryException
         */
        public function affectingStatement($query, $bindings = [])
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return 0;
                }

                try {

                    return $this->wpdb->doAffectingStatement($query, $bindings);


                }

                catch (mysqli_sql_exception $e) {

                    throw new QueryException($query, $bindings, $e);

                }


            });

        }


        /**
         * Get a new raw query expression.
         *
         * @param  mixed  $value
         *
         * @return Expression
         */
        public function raw($value) : Expression
        {

            return new Expression($value);
        }


        /**
         * Run a raw, unprepared query against the mysqli connection.
         *
         * @param  string  $query
         *
         * @return bool
         * @throws QueryException
         */
        public function unprepared($query)
        {

            return $this->runWpDB($query, [], function ($query) {

                if ($this->pretending) {
                    return true;
                }

                try {

                    return $this->wpdb->doUnprepared($query);


                }

                catch (mysqli_sql_exception $e) {

                    throw new QueryException($query, [], $e);

                }


            });


        }


        /**
         * Run a select statement against the database and returns a generator.
         * I dont believe that this is currently possible like it is with laravel,
         * since wpdb does not use PDO.
         * Every wpdb method seems to be iterating over the result array.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  bool  $useReadPdo
         *
         * @return Generator
         */
        public function cursor($query, $bindings = [], $useReadPdo = true) : Generator
        {

            /** @var mysqli_result|array $result */

            $result = $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return [];
                }

                try {

                    return $this->wpdb->doCursorSelect($query, $bindings);

                }

                catch (mysqli_sql_exception $e) {

                    throw new \Illuminate\Database\QueryException($query, $bindings, $e);

                }


            });

            // $result can be null if runWpDb catches an exception.
            if (is_array($result) || ! $result) {
                return;
            }

            while ($record = $result->fetch_assoc()) {

                yield $record;

            }

        }


        /**
         * Run a SQL statement through the wpdb class.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  Closure  $callback
         *
         * @return mixed
         * @throws QueryException
         */
        public function runWpDB(string $query, array $bindings, Closure $callback)
        {


            return $this->runWithoutExceptions(
                $query,
                $bindings = $this->prepareBindings($bindings),
                $callback
            );


        }


        private function runWithoutExceptions(string $query, array $bindings, Closure $callback)
        {

            $start = microtime(true);

            $result = $callback($query, $bindings);

            if ($this->logging_queries) {

                $this->logQuery($query, $bindings, $this->getElapsedTime($start));

            }

            return $result;

        }


        private function isConcurrencyError(Throwable $e) : bool
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


        /*
        |
        |
        |--------------------------------------------------------------------------
        | MISC getters and setters
        |--------------------------------------------------------------------------
        |
        |
        |
        |
        |
        |
        |
        */

        /**
         * Get an option from the configuration options.
         *
         * @param  string|null  $option
         *
         * @return mixed
         */
        public function getConfig($option = null)
        {

            return Arr::get($this->config, $option);
        }


        /**
         * Returns the name of the database connection.
         *
         * @return string
         */
        public function getDatabaseName() : string
        {

            return $this->db_name;

        }

        /**
         * Returns the name of the database connection.
         *
         * Redundant but required for Eloquent.
         *
         * @return string
         */
        public function getName() : string
        {

            return $this->getDatabaseName();

        }


        public function getTablePrefix() : string
        {

            return $this->table_prefix;

        }


        public function getPdo() : PdoAdapter
        {

            return $this->wpdb_to_pdo_adapter;

        }


    }