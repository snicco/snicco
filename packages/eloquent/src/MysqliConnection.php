<?php

declare(strict_types=1);

namespace Snicco\Database;

use Closure;
use Generator;
use mysqli_result;
use mysqli_sql_exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\MySqlConnection;
use Snicco\Database\Contracts\MysqliDriverInterface;

/**
 * @internal
 */
final class MysqliConnection extends MySqlConnection
{
    
    public const CONNECTION_NAME = 'wp_mysqli_connection';
    
    /**
     * @var MysqliDriverInterface
     */
    private $mysqli_driver;
    
    public function __construct(MysqliDriverInterface $mysqli_driver, string $table_prefix)
    {
        $this->mysqli_driver = $mysqli_driver;
        $pdo_adapter = function () {
            return $this->mysqli_driver;
        };
        
        parent::__construct($pdo_adapter, DB_NAME, $table_prefix, [
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            // important. Don't set this to an empty string as it is by default in WordPress.
            'collation' => ! empty(DB_COLLATE) ? DB_COLLATE : null,
            'prefix' => $table_prefix,
            'name' => self::CONNECTION_NAME,
        ]);
        
        $this->useDefaultSchemaGrammar();
    }
    
    public function lastInsertId() :int
    {
        return $this->mysqli_driver->lastInsertId();
    }
    
    /**
     * Run an SQL statement through the mysqli_driver class.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  Closure  $callback
     *
     * @return mixed
     */
    public function run($query, $bindings, Closure $callback)
    {
        $start = microtime(true);
        
        try {
            $result = $callback($query, $bindings = $this->prepareBindings($bindings));
        } catch (mysqli_sql_exception $mysqli_sql_exception) {
            throw new QueryException($query, $bindings, $mysqli_sql_exception);
        }
        
        if ($this->loggingQueries) {
            $this->logQuery($query, $bindings, $this->getElapsedTime($start));
        }
        
        return $result;
    }
    
    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo  can be ignored.
     *
     * @return mixed
     * @throws QueryException
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $result = $this->select($query, $bindings);
        return array_shift($result);
    }
    
    /**
     * @param  string  $query
     * @param  array  $bindings
     *
     * @return array
     * @throws QueryException
     */
    public function selectFromWriteConnection($query, $bindings = []) :array
    {
        return $this->select($query, $bindings);
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
    public function select($query, $bindings = [], $useReadPdo = true) :array
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending) {
                return [];
            }
            return $this->mysqli_driver->doSelect($query, $bindings);
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
    public function insert($query, $bindings = []) :bool
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
    public function statement($query, $bindings = []) :bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending) {
                return true;
            }
            return $this->mysqli_driver->doStatement($query, $bindings);
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
    public function update($query, $bindings = []) :int
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
    public function affectingStatement($query, $bindings = []) :int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending) {
                return 0;
            }
            
            return $this->mysqli_driver->doAffectingStatement($query, $bindings);
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
    public function delete($query, $bindings = []) :int
    {
        return $this->affectingStatement($query, $bindings);
    }
    
    /**
     * Run a raw, unprepared query against the mysqli connection.
     *
     * @param  string  $query
     *
     * @return bool
     * @throws QueryException
     */
    public function unprepared($query) :bool
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending) {
                return true;
            }
            return $this->mysqli_driver->doUnprepared($query);
        });
    }
    
    /**
     * Run a select statement against the database and returns a generator.
     * I don't believe that this is currently possible like it is with laravel,
     * since mysqli_driver does not use PDO.
     * Every mysqli_driver method seems to be iterating over the result array.
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
        $result = $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending) {
                return [];
            }
            return $this->mysqli_driver->doCursorSelect($query, $bindings);
        });
        
        if (is_array($result)) {
            return [];
        }
        
        while ($record = $result->fetch_assoc()) {
            yield $record;
        }
    }
    
    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if ( ! $this->mysqli_driver->isStillConnected()) {
            $this->mysqli_driver->reconnect();
        }
    }
    
}