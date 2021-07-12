<?php


    declare(strict_types = 1);


    namespace BetterWP\Database\Contracts;

    use BetterWP\Database\Illuminate\MySqlQueryGrammar;
    use BetterWP\Database\Illuminate\MySqlSchemaBuilder;
    use BetterWP\Database\Illuminate\MySqlSchemaGrammar;
    use BetterWpdb\PdoAdapter;
    use Illuminate\Database\ConnectionInterface as IlluminateConnectionInterface;
    use Illuminate\Database\Query\Builder as QueryBuilder;
    use Illuminate\Database\Query\Processors\MySqlProcessor;

    interface WPConnectionInterface extends IlluminateConnectionInterface
    {

        public function dbInstance() : BetterWPDbInterface;

        /**
         * Get the query grammar used by the connection.
         *
         * @return MySqlQueryGrammar
         */
        public function getQueryGrammar() :MySqlQueryGrammar ;


        /**
         * Get the query post processor used by the connection.
         *
         * @return MySqlProcessor
         */
        public function getPostProcessor() : MySqlProcessor;


        /**
         * Get a schema builder instance for the connection.
         *
         * @return
         */
        public function getSchemaBuilder() : MySqlSchemaBuilder;


        /**
         * Get the schema grammar used by the connection.
         *
         * @return  MySqlSchemaGrammar
         */
        public function getSchemaGrammar() : MySqlSchemaGrammar;


        /**
         * Prepare the query bindings for execution.
         *
         * @param  array  $bindings
         *
         * @return array
         */
        public function prepareBindings( array $bindings ) : array;


        /**
         * Get a new query builder instance.
         *
         * @return QueryBuilder
         */
        public function query() : QueryBuilder;

        /**
         * Return the table prefix used by the connection.
         *
         * @return string
         */
        public function getTablePrefix() : string;


        /**
         * Get the connection name.
         *
         * This is the value provided in the config. Not the actual database name
         *
         * @return string|null
         */
        public function getName() : string;


        /**
         * Get an option from the configuration options.
         *
         * @param  string|null  $option
         *
         * @return mixed
         */
        public function getConfig( $option = null );


        /**
         * Run a select statement against the database.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return array
         */
        public function selectFromWriteConnection( $query, $bindings = [] );


        /**
         *
         */
        public function lastInsertId() :int;

    }