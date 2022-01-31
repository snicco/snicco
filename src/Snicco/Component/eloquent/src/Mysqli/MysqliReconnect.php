<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Mysqli;

use mysqli;
use Closure;
use RuntimeException;

/**
 * @interal
 */
final class MysqliReconnect
{
    
    private Closure $reconnect_callable;
    
    public function __construct(Closure $reconnect_callable)
    {
        $this->reconnect_callable = $reconnect_callable;
    }
    
    /**
     * @throws RuntimeException
     */
    public function getMysqli() :mysqli
    {
        $mysqli = call_user_func($this->reconnect_callable);
        
        if ($mysqli instanceof mysqli) {
            return $mysqli;
        }
        
        throw new RuntimeException("Cant reconnect with the database.");
    }
    
}