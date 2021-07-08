<?php


    declare(strict_types = 1);


    namespace Tests\integration\Database;

    use BetterWP\Database\Contracts\BetterWPDbInterface;
    use BetterWP\Database\DatabaseServiceProvider;
    use BetterWP\Database\FakeDB;
    use BetterWP\Database\WPConnection;
    use Tests\TestCase;

    class DatabaseTestCase extends TestCase
    {

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

        public function packageProviders() : array
        {

            return [
                DatabaseServiceProvider::class,
            ];
        }

    }