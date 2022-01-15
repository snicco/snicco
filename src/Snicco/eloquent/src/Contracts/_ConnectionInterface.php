<?php

declare(strict_types=1);

namespace Snicco\Database\Contracts;

use PDO;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\ConnectionInterface as IlluminateConnectionInterface;

/**
 * This is the real interface that all connection instances have to confirm to.
 * The laravel ConnectionInterface is not complete at all.
 * Keep this around in case we have to create our own connection class instead of extending the
 * illuminate MySQL Connection.
 *
 * @internal
 * @deprecated In previous versions we used this interface to create our own connection class
 *     instead of the PDO adapter. But this basically means that we have to maintain our own fork
 *     of the illuminate/database package since eloquent in many places does not depend on the
 *     interface as it should but on the {@see Connection} class.s
 */
interface _ConnectionInterface extends IlluminateConnectionInterface
{
    
    /**
     * Get the query grammar used by the connection.
     *
     * @return \Illuminate\Database\Query\Grammars\MySqlGrammar
     */
    public function getQueryGrammar();
    
    /**
     * Get the query post processor used by the connection.
     *
     * @return MySqlProcessor
     */
    public function getPostProcessor();
    
    /**
     * Get a schema builder instance for the connection.
     *
     * @return MySqlBuilder
     */
    public function getSchemaBuilder();
    
    /**
     * Get the schema grammar used by the connection.
     *
     * @return  MySqlGrammar
     */
    public function getSchemaGrammar();
    
    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     *
     * @return array
     */
    public function prepareBindings(array $bindings) :array;
    
    /**
     * Get a new query builder instance.
     *
     * @return QueryBuilder
     */
    public function query() :QueryBuilder;
    
    /**
     * Return the table prefix used by the connection.
     *
     * @return string
     */
    public function getTablePrefix() :string;
    
    /**
     * Get the connection name.
     * This is the value provided in the config. Not the actual database name
     *
     * @return string|null
     */
    public function getName() :string;
    
    /**
     * Get an option from the configuration options.
     *
     * @param  string|null  $option
     *
     * @return mixed
     */
    public function getConfig($option = null);
    
    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     *
     * @return array
     */
    public function selectFromWriteConnection($query, $bindings = []);
    
    /**
     * Return the last insert id.
     */
    public function lastInsertId() :int;
    
    /**
     * @return PDO
     */
    public function getPDO();
    
}