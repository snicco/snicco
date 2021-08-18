<?php

declare(strict_types=1);

namespace Snicco\Database;

use Closure;
use DateTime;
use Generator;
use mysqli_result;
use mysqli_sql_exception;
use Illuminate\Support\Arr;
use Illuminate\Database\Grammar;
use Illuminate\Database\QueryException;
use Illuminate\Database\Query\Expression;
use Snicco\Database\Concerns\LogsQueries;
use Snicco\Database\Illuminate\MySqlProcessor;
use Snicco\Database\Concerns\ManagesTransactions;
use Snicco\Database\Illuminate\MySqlQueryGrammar;
use Snicco\Database\Contracts\BetterWPDbInterface;
use Snicco\Database\Illuminate\MySqlSchemaBuilder;
use Snicco\Database\Illuminate\MySqlSchemaGrammar;
use Snicco\Database\Contracts\WPConnectionInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Processors\MySqlProcessor as IlluminateProcessor;

class WPConnection implements WPConnectionInterface
{
    
    use ManagesTransactions;
    use LogsQueries;
    
    protected BetterWPDbInterface $wpdb;
    protected string              $db_name;
    protected string              $table_prefix;
    protected MySqlQueryGrammar   $query_grammar;
    protected MySqlSchemaGrammar  $schema_grammar;
    protected MySqlProcessor      $post_processor;
    protected bool                $pretending      = false;
    protected bool                $logging_queries = false;
    /**
     * The database connection configuration options.
     *
     * @todo check how this is used.
     * @var array
     */
    protected array $config = [];
    private string  $name;
    
    public function __construct(BetterWPDbInterface $wpdb, string $name)
    {
        $this->wpdb = $wpdb;
        $this->db_name = $wpdb->dbname;
        $this->table_prefix = $wpdb->prefix;
        $this->query_grammar = $this->withTablePrefix(new MySqlQueryGrammar());
        $this->schema_grammar = $this->withTablePrefix(new MySqlSchemaGrammar());
        $this->post_processor = new MySqlProcessor();
        $this->name = $name;
    }
    
    /**
     * Set the table prefix and return the grammar for
     * a QueryBuilder or SchemaBuilder
     *
     * @param  Grammar  $grammar
     *
     * @return Grammar
     */
    private function withTablePrefix(Grammar $grammar) :Grammar
    {
        $grammar->setTablePrefix($this->table_prefix);
        
        return $grammar;
    }
    
    public function dbInstance() :BetterWPDbInterface
    {
        
        return $this->wpdb;
    }
    
    /**
     * Get the schema grammar used by the connection.
     *
     * @return MySqlSchemaGrammar
     */
    public function getSchemaGrammar() :MySqlSchemaGrammar
    {
        
        return $this->schema_grammar;
    }
    
    /**
     * Get a schema builder instance for the connection.
     *
     * @return MySqlSchemaBuilder
     */
    public function getSchemaBuilder() :MySqlSchemaBuilder
    {
        
        return new MySqlSchemaBuilder($this);
        
    }
    
    /**
     * Begin a fluent query against a database table.
     *
     * @param  Closure| QueryBuilder |string  $table
     * @param  string|null  $as
     *
     * @return QueryBuilder
     */
    public function table($table, $as = null) :QueryBuilder
    {
        
        return $this->query()->from($table, $as);
        
    }
    
    /**
     * Get a new query builder instance.
     *
     * @return QueryBuilder
     */
    public function query() :QueryBuilder
    {
        
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
        
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
     * Get the query grammar used by the connection.
     * The QueryGrammar is used to "translate" the QueryBuilder instance into raw
     * SQL
     *
     * @return MySqlQueryGrammar ;
     */
    public function getQueryGrammar() :MySqlQueryGrammar
    {
        
        return $this->query_grammar;
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
     * Get the query post processor used by the connection.
     *
     * @return MySqlProcessor
     */
    public function getPostProcessor() :IlluminateProcessor
    {
        return $this->post_processor;
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
            $this->prepareBindings($bindings),
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
    
    // Method is used by eloquent for pdo calls.
    
    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     *
     * @return array
     */
    public function prepareBindings(array $bindings) :array
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
                $bindings[$key] = $binding->format(
                    $this->getQueryGrammar()
                         ->getDateFormat()
                );
                
            }
        }
        
        return $bindings;
        
    }
    
    public function selectFromWriteConnection($query, $bindings = []) :array
    {
        
        return $this->select($query, $bindings, false);
        
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
                
            } catch (mysqli_sql_exception $e) {
                
                throw new QueryException($query, $bindings, $e);
                
            }
            
        });
        
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
                
            } catch (mysqli_sql_exception $e) {
                
                throw new QueryException($query, $bindings, $e);
                
            }
            
        });
        
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
                
            } catch (mysqli_sql_exception $e) {
                
                throw new QueryException($query, $bindings, $e);
                
            }
            
        });
        
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
     * Get a new raw query expression.
     *
     * @param  mixed  $value
     *
     * @return Expression
     */
    public function raw($value) :Expression
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
                
            } catch (mysqli_sql_exception $e) {
                
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
    public function cursor($query, $bindings = [], $useReadPdo = true) :Generator
    {
        
        /** @var mysqli_result|array $result */
        
        $result = $this->runWpDB($query, $bindings, function ($query, $bindings) {
            
            if ($this->pretending) {
                return [];
            }
            
            try {
                
                return $this->wpdb->doCursorSelect($query, $bindings);
                
            } catch (mysqli_sql_exception $e) {
                
                throw new QueryException($query, $bindings, $e);
                
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
    public function getDatabaseName() :string
    {
        
        return $this->db_name;
        
    }
    
    public function getName() :string
    {
        return $this->name;
    }
    
    public function getTablePrefix() :string
    {
        
        return $this->table_prefix;
        
    }
    
    public function lastInsertId() :int
    {
        return $this->wpdb->lastInsertId();
    }
    
}