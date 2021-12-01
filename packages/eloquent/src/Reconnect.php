<?php

declare(strict_types=1);

namespace Snicco\Database;

use mysqli;
use Closure;
use RuntimeException;

final class Reconnect
{
    
    /**
     * @var Closure
     */
    private $reconnect_callable;
    
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