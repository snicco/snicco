<?php

declare(strict_types=1);

namespace Snicco\Database\Contracts;

use mysqli_result;
use RuntimeException;

/**
 * @internal
 */
interface MysqliDriverInterface extends PDOAdapter
{
    
    public function doSelect(string $sql, array $bindings) :array;
    
    public function doStatement(string $sql, array $bindings) :bool;
    
    public function doAffectingStatement($sql, array $bindings) :int;
    
    public function doUnprepared(string $sql) :bool;
    
    public function doCursorSelect(string $sql, array $bindings) :mysqli_result;
    
    public function isStillConnected() :bool;
    
    /**
     * @return bool
     * @throws RuntimeException
     */
    public function reconnect() :bool;
    
}