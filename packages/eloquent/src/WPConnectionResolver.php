<?php

namespace Snicco\Database;

use Illuminate\Database\ConnectionInterface;
use Snicco\Database\Contracts\MysqliConnectionFactory;
use Illuminate\Database\ConnectionResolverInterface as IlluminateConnectionResolver;

/**
 * This class is an adapter around to illuminate-connection-resolver.
 * WordPress will ALWAYS open a mysqli connection. We will use this connection as the default
 * connection in order to not open up an unneeded secondary PDO connection to the same database.
 * Any database name that is NOT the default name will be passed to eloquent. This way we get the
 * best of both worlds. The developer can use secondary db connections with for example postgres or
 * mongo if needed, but we don't open another connection by default.
 *
 * @internal
 */
final class WPConnectionResolver implements IlluminateConnectionResolver
{
    
    /**
     * @var string
     */
    private $default_connection;
    
    /**
     * @var ConnectionInterface
     */
    private $mysqli_connection;
    
    /**
     * @var IlluminateConnectionResolver
     */
    private $connection_resolver;
    
    /**
     * @var MysqliConnectionFactory
     */
    private $mysqli_connection_factory;
    
    public function __construct(IlluminateConnectionResolver $connection_resolver, MysqliConnectionFactory $mysqli_factory)
    {
        $this->connection_resolver = $connection_resolver;
        $this->mysqli_connection_factory = $mysqli_factory;
        $this->default_connection = MysqliConnection::CONNECTION_NAME;
    }
    
    /**
     * Handle calls from the DB Facade and proxy them to the default connection
     * if the user did not request a specific connection via the DB::connection() method;
     */
    public function __call(string $method, array $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
    
    /**
     * @param  string  $name
     *
     * @return ConnectionInterface
     */
    public function connection($name = null) :ConnectionInterface
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
    public function getDefaultConnection() :string
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
    
    private function resolveConnection(string $name) :ConnectionInterface
    {
        if ($name !== $this->getDefaultConnection()) {
            return $this->connection_resolver->connection($name);
        }
        
        if (isset($this->mysqli_connection)) {
            return $this->mysqli_connection;
        }
        else {
            $this->mysqli_connection = $this->mysqli_connection_factory->create();
        }
        
        return $this->mysqli_connection;
    }
    
}
