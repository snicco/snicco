<?php


    declare(strict_types = 1);


    namespace Tests\integration\Database;

    use Snicco\Database\Contracts\BetterWPDbInterface;
    use Snicco\Database\DatabaseServiceProvider;
    use Snicco\Database\FakeDB;
    use Snicco\Database\Testing\Assertables\AssertableWpDB;
    use Snicco\Database\WPConnection;
    use Illuminate\Container\Container;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Facade;
    use mysqli;
    use Tests\TestCase;

    class DatabaseTestCase extends TestCase
    {

        protected function setUp() : void
        {

            $traits = class_uses_recursive(static::class);

            if ( in_array(WithTestTables::class, $traits)) {

                $this->afterApplicationCreated(function () {

                    $this->removeWpBrowserTransaction();
                    $this->withNewTables();

                });

            }

            if ( in_array(WithTestTransactions::class, $traits)) {

                $this->afterApplicationCreated(function () {

                    $this->beginTransaction();

                });

                $this->beforeApplicationDestroyed(function () {

                    $this->rollbackTransaction();

                });

            }


            parent::setUp();

        }

        /**
         * NOTE: THIS DATABASE HAS TO EXISTS ON THE LOCAL MACHINE.
         */
        protected function secondDatabaseConfig() : array
        {

            return [
                'username' => $_SERVER['SECONDARY_DB_USER'],
                'database' => $_SERVER['SECONDARY_DB_NAME'],
                'password' => $_SERVER['SECONDARY_DB_PASSWORD'],
                'host' => $_SERVER['SECONDARY_DB_HOST'],
            ];
        }

        protected function assertDefaultConnection(BetterWPDbInterface $wpdb) : void
        {
            $this->assertSame(DB_NAME, $wpdb->dbname);
        }

        protected function assertNotDefaultConnection(BetterWPDbInterface $wpdb) : void
        {

            $this->assertNotSame(DB_NAME, $wpdb->dbname);

        }

        protected function withFakeDb() : DatabaseTestCase
        {
            $this->instance(BetterWPDbInterface::class, FakeDB::class);
            return $this;
        }

        protected function assertable(WPConnection $connection) :FakeDB {
            return $connection->dbInstance();
        }

        protected function wpdbInsert(string $table, array $data)
        {

            global $wpdb;

            $format = $this->format($data);

            $success = $wpdb->insert($table, $data, $format) !== false;

            if ( ! $success) {
                $this->fail('Failed to insert with wpdb for test setup.');
            }

        }

        protected function wpdbUpdate(string $table, array $data, array $where)
        {

            global $wpdb;

            $where_format = $this->format($where);
            $data_format = $this->format($data);

            $success = $wpdb->update($table, $data, $where, $data_format, $where_format) !== false;

            if ( ! $success) {
                $this->fail('Failed to update with wpdb.');
            }

        }

        protected function wpdbDelete(string $table, array $wheres) {

            global $wpdb;

            $wpdb->delete($table, $wheres, $this->format($wheres));

        }

        protected function assertDbTable(string $table_name) : AssertableWpDB
        {

            return new AssertableWpDB($table_name);
        }

        // WordPress is completely incompatible with mysql strict mode.
        // But we are not testing $wpdb here and can only test if our tables are created correctly.
        protected function ensureFailOnErrors()
        {
            global $wpdb;

            /** @var mysqli $mysqli */
            $mysqli = $wpdb->dbh;
            $mysqli->query("SET SESSION sql_mode='TRADITIONAL'");

        }

        protected function removeWpBrowserTransaction()
        {

            global $wpdb;
            $wpdb->query('COMMIT');
        }

        public function packageProviders() : array
        {

            return [
                DatabaseServiceProvider::class,
            ];
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