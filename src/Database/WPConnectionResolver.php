<?php


    namespace Snicco\Database;

    use Snicco\Database\Contracts\ConnectionResolverInterface;
    use Snicco\Database\Contracts\WPConnectionInterface;
    use Snicco\ExceptionHandling\Exceptions\ConfigurationException;
    use Snicco\ExceptionHandling\Exceptions\Exception;
    use Snicco\Support\Arr;
    use wpdb;

    class WPConnectionResolver implements ConnectionResolverInterface
    {


        /** @var string */
        private $default_connection;

        /**
         * @var array
         */
        private $connections;

        /**
         * @var WPConnectionInterface[]
         */
        private $instantiated_connections;
        /**
         * @var string
         */
        private $db_class;

        /**
         *
         * 'connection_name' => [
         *   'username',
         *   'database',
         *   'password',
         *   'host'
         * }
         *
         * @param  array  $connections
         * @param  string  $db_class
         */
        public function __construct(array $connections, string $db_class)
        {
            $this->connections = $connections;
            $this->db_class = $db_class;
        }

        /**
         * Get a database connection instance.
         *
         * @param  string  $name
         *
         * @return WPConnectionInterface
         * @throws ConfigurationException
         *
         */
        public function connection($name = null) : WPConnectionInterface
        {

            if (is_null($name)) {
                $name = $this->getDefaultConnection();
            }

            return $this->resolveConnection($name);

        }

        /**
         * Get the default connection name.
         *
         * @return string
         */
        public function getDefaultConnection() : string
        {

            return $this->default_connection;
        }

        /**
         * Set the default connection name.
         *
         * @param  string  $name
         *
         * @return void
         */
        public function setDefaultConnection($name)
        {

            $this->default_connection = $name;
        }

        /**
         * Handle calls from the DB Facade and proxy them to the default connection
         * if the user did not request a specific connection via the DB::connection() method;
         */
        public function __call(string $method, array $parameters)
        {
            return $this->connection()->$method(...$parameters);
        }

        private function resolveConnection(string $name) : WPConnectionInterface
        {

            if (isset($this->instantiated_connections[$name])) {
                return $this->instantiated_connections[$name];
            }

            if ( ! isset($this->connections[$name])) {
                throw new ConfigurationException("Invalid database connection [$name] used.");
            }

            $wpdb = $this->createWPDb($this->connections[$name], $name);
            $mysqli = $this->extractMySQLi($wpdb);

            $connection = new WPConnection(new $this->db_class($wpdb, $mysqli), $name);

            $this->instantiated_connections[$name] = $connection;

            return $connection;


        }

        private function createWPDb(array $config, string $name) : wpdb
        {

            if ($this->isDefaultWordPressConnection($config)) {
                global $wpdb;
                return $wpdb;
            }

            try {

                $wpdb = new wpdb(
                    $config['username'],
                    $config['password'],
                    $config['database'],
                    $config['host']
                );

                return $wpdb;

            } catch ( \Throwable $e) {

                throw new ConfigurationException("Unable to create a wpdb connection with the config for connection [$name].", $e->getCode() , $e);

            }


        }

        private function extractMySQLi(wpdb $wpdb)
        {
            try {

                // This is a protected property in wpdb but it has __get() access.
                return $wpdb->dbh;

            }

            catch (\Throwable $e) {

                // This will work for sure if Wordpress where ever
                // to delete magic method accessors, which tbh will probably never happen. Lol.
                return (function () {

                    return $this->dbh;

                })->call($wpdb);

            }
        }

        private function isDefaultWordPressConnection(array $config) : bool
        {

            return array_diff(array_values($config), [
                    DB_USER,
                    DB_NAME,
                    DB_PASSWORD,
                    DB_HOST,
                ]) === [];
        }

    }
