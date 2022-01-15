<?php

declare(strict_types=1);

namespace Snicco\Database\Contracts;

/**
 * @internal
 * This interface acts as a bridge between our Mysqli Connection and the places where
 * laravel operates directly on the PDO instance Mainly in {@see ManagesTransactions} If at any
 * point the starts to typehint against an actual PDO instance we have to rewrite this.
 * It does not look like this tho. Not even in 9.X
 * The alternative to using a PDO Adapter is to fork the illuminate/database package and replace all
 * hardcoded type dependencies on the concrete Connection class with the ConnectionInterface since
 * laravel unfortunately doesn't depend on the abstraction in many places.
 */
interface PDOAdapter
{
    
    /**
     * @see \PDO::commit()
     */
    public function commit() :bool;
    
    /**
     * @see \PDO::beginTransaction()
     */
    public function beginTransaction() :bool;
    
    /**
     * @return int|false
     * @see \PDO::exec()
     */
    public function exec($statement);
    
    /**
     * @see \PDO::rollBack()
     */
    public function rollback() :bool;
    
    public function lastInsertId() :int;
    
}
