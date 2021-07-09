<?php


    declare(strict_types = 1);


    namespace BetterWP\Database;

    use BetterWP\Database\Concerns\DelegatesToWpdb;
    use BetterWP\Database\Contracts\BetterWPDbInterface;
    use mysqli;
    use mysqli_result;
    use mysqli_stmt;
    use wpdb;

    /**
     * @property string $dbuser;
     * @property string $dbpassword;
     * @property string $dbhost;
     * @property string $dbname;
     * @property string $prefix;
     */
    class BetterWPDb implements BetterWPDbInterface
    {

        use DelegatesToWpdb;

        /**
         * @var mysqli
         */
        private $mysqli;

        /**
         * @var wpdb
         */
        private $wpdb;

        public function __construct(wpdb $wpdb, mysqli $mysqli)
        {
            $this->mysqli = $mysqli;
            $this->wpdb = $wpdb;

            mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        }

        public function doSelect( $sql, $bindings ) : array {

            $stmt = $this->preparedStatement( $sql, $bindings );

            $stmt->execute();

            return $stmt->get_result()->fetch_all( MYSQLI_ASSOC ) ?? [];

        }

        public function doStatement( string $sql, array $bindings ) : bool {

            if ( empty( $bindings ) ) {

                $result = $this->mysqli->query( $sql );

                return $result !== false;

            }

            $stmt = $this->preparedStatement( $sql, $bindings );

            return $stmt->execute();

        }

        public function doAffectingStatement( $sql, array $bindings ) : int {

            if ( empty( $bindings ) ) {

                $this->mysqli->query( $sql );

                return $this->mysqli->affected_rows;

            }

            $this->preparedStatement( $sql, $bindings )->execute();

            return $this->mysqli->affected_rows;


        }

        public function doUnprepared( string $sql ) : bool {

            $result = $this->mysqli->query( $sql );

            return $result !== false;

        }

        public function doCursorSelect( string $sql, array $bindings ) : mysqli_result {

            $statement = $this->preparedStatement( $sql, $bindings );

            $statement->execute();

            return $statement->get_result();

        }

        public function startTransaction() {

            $this->mysqli->begin_transaction();
        }

        public function commitTransaction() {

            $this->mysqli->commit();
        }

        public function rollbackTransaction( string $sql ) {

            $this->mysqli->query( $sql );

        }

        public function createSavepoint( string $sql ) {

            $this->mysqli->query( $sql );

        }

        /**
         * @param $sql
         * @param $bindings
         * @param  string  $types
         *
         * @return false|mysqli_stmt
         */
        private function preparedStatement($sql, $bindings, string $types = "")
        {

            $types = $types ? : str_repeat("s", count($bindings));
            $stmt = $this->mysqli->prepare($sql);

            if ( ! empty($bindings) ) {

                $stmt->bind_param($types, ...$bindings);

            }

            return $stmt;
        }

    }