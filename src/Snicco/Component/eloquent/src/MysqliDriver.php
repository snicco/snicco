<?php

declare(strict_types=1);

namespace Snicco\Database;

use mysqli;
use mysqli_stmt;
use mysqli_result;
use RuntimeException;
use mysqli_sql_exception;
use Illuminate\Database\QueryException;
use Snicco\Database\Contracts\MysqliDriverInterface;

/**
 * @internal
 */
final class MysqliDriver implements MysqliDriverInterface
{
    
    /**
     * @var mysqli
     */
    private $mysqli;
    
    /**
     * @var Reconnect
     */
    private $reconnect;
    
    public function __construct(mysqli $mysqli, Reconnect $reconnect)
    {
        $this->mysqli = $mysqli;
        $this->reconnect = $reconnect;
    }
    
    public function doSelect($sql, $bindings) :array
    {
        $stmt = $this->getPreparedStatement($sql, $bindings);
        
        $stmt->execute();
        
        $values = [];
        
        $result = $stmt->get_result();
        
        if ( ! $result instanceof mysqli_result) {
            throw new QueryException(
                $sql,
                $bindings,
                new mysqli_sql_exception(implode(",", $this->mysqli->error_list))
            );
        }
        
        while ($row = $result->fetch_object()) {
            $values[] = $row;
        }
        
        return $values;
    }
    
    public function doStatement(string $sql, array $bindings) :bool
    {
        if (empty($bindings)) {
            $result = $this->mysqli->query($sql);
            
            return $result !== false;
        }
        
        $stmt = $this->getPreparedStatement($sql, $bindings);
        
        return $stmt->execute();
    }
    
    public function doAffectingStatement($sql, array $bindings) :int
    {
        if (empty($bindings)) {
            $this->mysqli->query($sql);
            
            return $this->mysqli->affected_rows;
        }
        
        $statement = $this->getPreparedStatement($sql, $bindings);
        $statement->execute();
        
        return $statement->affected_rows;
    }
    
    public function doUnprepared(string $sql) :bool
    {
        $result = $this->mysqli->query($sql);
        
        return $result !== false;
    }
    
    public function doCursorSelect(string $sql, array $bindings) :mysqli_result
    {
        $statement = $this->getPreparedStatement($sql, $bindings);
        
        $statement->execute();
        
        return $statement->get_result();
    }
    
    public function lastInsertId() :int
    {
        return $this->mysqli->insert_id;
    }
    
    public function isStillConnected() :bool
    {
        return $this->mysqli->ping();
    }
    
    public function reconnect() :bool
    {
        $this->mysqli = $this->reconnect->getMysqli();
        return true;
    }
    
    public function commit() :bool
    {
        return $this->mysqli->commit();
    }
    
    public function beginTransaction() :bool
    {
        return $this->mysqli->begin_transaction();
    }
    
    public function exec($statement)
    {
        return $this->doAffectingStatement($statement, []);
    }
    
    public function rollback() :bool
    {
        return $this->mysqli->rollback();
    }
    
    /**
     * @param $sql
     * @param $bindings
     *
     * @return mysqli_stmt
     * @throws QueryException
     */
    private function getPreparedStatement($sql, $bindings) :mysqli_stmt
    {
        $stmt = $this->mysqli->prepare($sql);
        
        if ( ! $stmt instanceof mysqli_stmt) {
            throw new QueryException($sql, $bindings, new RuntimeException($this->mysqli->error));
        }
        
        if (empty($bindings)) {
            return $stmt;
        }
        
        $types = '';
        
        foreach ($bindings as $binding) {
            if (is_double($binding)) {
                $types .= 'd';
            }
            elseif (is_int($binding)) {
                $types .= 'i';
            }
            else {
                $types .= 's';
            }
        }
        
        $success = $stmt->bind_param($types, ...$bindings);
        
        if ( ! $success) {
            throw new QueryException(
                $sql, $bindings, new RuntimeException($this->mysqli->error)
            );
        }
        
        return $stmt;
    }
    
}