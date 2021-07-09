<?php


    declare(strict_types = 1);


    namespace BetterWP\Database\Testing\Assertables;

    use BetterWP\Support\Str;
    use PHPUnit\Framework\Assert as PHPUnit;

    use function PHPUnit\Framework\isNull;

    class AssertableWpDB
    {

        /**
         * @var string
         */
        private $table;

        /**
         * @var \wpdb
         */
        private $wpdb;

        public function __construct(string $table)
        {
            $this->table = $table;

            global $wpdb;
            $this->wpdb = $wpdb;

        }

        public function assertRecordExists (array $column_conditions ) {

            $wheres = '';
            $values = [];

            foreach ($column_conditions as $column => $value) {

                if ( Str::endsWith($wheres, ['%f', '%d', '%s'] ) ) {
                    $wheres .= " AND ";
                }

                if ( is_float( $value ) ) {
                    $wheres .= $column . ' = %f';
                }

                if ( is_int( $value) ) {
                    $wheres .= $column . ' = %d';
                }

                if ( is_string ($value ) ) {
                    $wheres .= $column . ' = %s';
                }

                $values[] = $value;

            }

            $query = $this->wpdb->prepare("SELECT EXISTS(SELECT 1 FROM $this->table WHERE $wheres LIMIT 1)", $values);

            $exists = $this->wpdb->get_var($query);

            $result = (is_string($exists) && $exists === '1');

            $record_as_string = '';

            foreach ($column_conditions as $column => $value) {

                $record_as_string .= "$column => $value,";

            }

            $record_as_string = trim($record_as_string, ',');

            PHPUnit::assertTrue($result, "The record [$record_as_string] was not found in the table [$this->table].");

        }

        public function assertRecordNotExists (array $column_conditions) {

            [$wheres, $values] = $this->compile($column_conditions);

            $query = $this->wpdb->prepare("SELECT EXISTS(SELECT 1 FROM $this->table WHERE $wheres LIMIT 1)", $values);

            $exists = $this->wpdb->get_var($query);

            $record_as_string = '';

            foreach ($column_conditions as $column => $value) {

                $record_as_string .= "$column => $value,";

            }

            $record_as_string = trim($record_as_string, ',');

            PHPUnit::assertSame("0", $exists, "The record [$record_as_string] was unexpectedly found in the table [$this->table].");

        }

        public function assertRecordEquals($conditions, array $expected)
        {
            [$wheres, $values] = $this->compile($conditions);

            $record = $this->wpdb->get_row(
                $this->wpdb->prepare("SELECT * FROM $this->table WHERE $wheres LIMIT 1", $values),
                ARRAY_A
            );

            PHPUnit::assertSame($expected, $record, 'The record does not exists as specified.');


        }

        private function compile($conditions) : array
        {

            $wheres = '';
            $values = [];

            foreach ($conditions as $column => $value) {

                if ( Str::endsWith($wheres, ['%f', '%d', '%s'] ) ) {
                    $wheres .= " AND ";
                }

                if ( is_float( $value ) ) {
                    $wheres .= "`$column`" . ' = %f';
                }

                if ( is_int( $value) ) {
                    $wheres .= "`$column`" . ' = %d';
                }

                if ( is_string ($value ) ) {
                    $wheres .= "`$column`" . ' = %s';
                }

                $values[] = $value;

            }

            return [$wheres, $values];
        }

        private function format(array $data ) {

            $format = [];
            foreach ($data as $item) {

                if (is_float($item)) {
                    $format[] = "%f";
                }

                if (is_int($item)) {
                    $format[] = "%d";
                }

                if (is_string($item)) {
                    $format[] = "%s";
                }

            }

            return $format;

        }


    }