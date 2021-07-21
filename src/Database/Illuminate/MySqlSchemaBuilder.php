<?php


    namespace Snicco\Database\Illuminate;

    use Illuminate\Database\Schema\MySqlBuilder as IlluminateSchemaBuilder;
    use Snicco\Support\Str;
    use Closure;

    class MySqlSchemaBuilder extends IlluminateSchemaBuilder
    {

        /**
         *
         * Alias for the table method.
         *
         * @param  string  $table
         * @param  Closure  $closure
         */
        public function modify(string $table, Closure $closure)
        {

            $this->table($table, $closure);

        }

        public function getAllTables() : array
        {

            $parent = collect(parent::getAllTables());

            $key = 'Tables_in_'.$this->connection->getDatabaseName();

            return $parent->pluck($key)->toArray();


        }

        public function getColumnsByOrdinalPosition($table)
        {

            $query = $this->grammar->compileGetFullColumnInfo();

            $bindings = [$this->connection->getTablePrefix().$table];

            return $this->connection->runWpDB($query, $bindings, function ($query, $bindings ) {


                $col_info = collect(
                    $this->connection->select(
                        Str::replaceArray('?', $bindings, $query)
                    )
                );

                return $col_info->pluck('Field')->toArray();


            });


        }

        public function getTableCollation($table)
        {

            $query = $this->grammar->compileGetTableCollation();

            $bindings = [$this->connection->getTablePrefix().$table];

            return $this->connection->runWpDB($query, $bindings, function ($query, $bindings) {

                $results = $this->connection->select($query, $bindings);

                return $results[0]['Collation'];


            });

        }

        public function getTableCharset($table) : string
        {

            $collation = $this->getTableCollation($table);

            return Str::before($collation, '_');

        }

        public function getFullColumnInfo($table) : array
        {

            $query = $this->grammar->compileGetFullColumnInfo();

            $binding = $this->connection->getTablePrefix().$table;

            return $this->connection->runWpDB($query, [$binding], function ($query, $bindings) {

                $col_info = collect(
                    $this->connection->select(
                        Str::replaceArray('?', $bindings, $query)
                    )
                );

                $field_names = $col_info->pluck('Field');

                return $field_names->combine($col_info)->toArray();

            });


        }

        /**
         * Get the data type for the given column name.
         *
         * @param  string  $table
         * @param  string  $column
         *
         * @return string
         */
        public function getColumnType($table, $column) : string
        {

            return $this->getFullColumnInfo($table)[$column]['Type'] ?? '';

        }

    }