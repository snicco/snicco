<?php


    declare(strict_types = 1);


    namespace Snicco\Database\Illuminate;

    use Illuminate\Database\Query\Builder;
    use Illuminate\Database\Query\Processors\MySqlProcessor as IlluminateMySqlProcessor;

    class MySqlProcessor extends IlluminateMySqlProcessor
    {

        /**
         * Process an  "insert get ID" query.
         *
         * @param  Builder  $query
         * @param  string  $sql
         * @param  array  $values
         * @param  string|null  $sequence
         * @return int
         */
        public function processInsertGetId(Builder $query, $sql, $values, $sequence = null) : int
        {
            $connection = $query->getConnection();

            $connection->insert($sql, $values);

            $id = $connection->lastInsertId();

            return is_numeric($id) ? (int) $id : $id;
        }

    }