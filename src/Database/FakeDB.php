<?php

declare(strict_types=1);

namespace Snicco\Database;

use wpdb;
use Closure;
use mysqli_result;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Database\Concerns\DelegatesToWpdb;
use Snicco\Database\Contracts\BetterWPDbInterface;

class FakeDB implements BetterWPDbInterface
{
    
    use DelegatesToWpdb;
    
    private wpdb  $wpdb;
    private array $queries       = [];
    private array $return_values = [];
    private int   $last_id       = 0;
    
    public function __construct(wpdb $wpdb)
    {
        
        $this->wpdb = $wpdb;
    }
    
    public function assertDidUnprepared(string $sql)
    {
        
        $this->assertDid('doUnprepared', $sql, []);
    }
    
    public function assertDid(string $method, string $sql, array $bindings)
    {
        
        PHPUnit::assertArrayHasKey($method, $this->queries, "Method [$method] was not called.");
        
        $query = $this->queries[$method];
        
        PHPUnit::assertSame($sql, $query['sql'], "Incorrect SQL statement run.");
        PHPUnit::assertSame($bindings, $query['bindings'], "Incorrect bindings.");
        
    }
    
    public function assertDidSelect(string $sql, array $bindings)
    {
        
        $this->assertDid('doSelect', $sql, $bindings);
    }
    
    public function assertDidInsert(string $sql, array $bindings)
    {
        
        $this->assertDid('doStatement', $sql, $bindings);
    }
    
    public function assertDidUpdate(string $sql, array $bindings)
    {
        
        $this->assertDid('doAffectingStatement', $sql, $bindings);
    }
    
    public function assertDidDelete(string $sql, array $bindings)
    {
        
        $this->assertDid('doAffectingStatement', $sql, $bindings);
    }
    
    public function assertDidCursorSelect(string $sql, array $bindings)
    {
        
        $this->assertDid('doCursorSelect', $sql, $bindings);
    }
    
    public function assertDidNotDoSelect()
    {
        
        $this->assertDidNot('doSelect');
    }
    
    public function assertDidNot(string $method)
    {
        
        PHPUnit::assertArrayNotHasKey(
            $method,
            $this->queries,
            "Method [$method] was called unexpectedly."
        );
        
    }
    
    public function assertDidNotDoStatement()
    {
        
        $this->assertDidNot('doStatement');
    }
    
    public function assertDidNotDoUpdate()
    {
        
        $this->assertDidNot('doAffectingStatement');
        
    }
    
    public function assertDidNotDoDelete()
    {
        
        $this->assertDidNot('doAffectingStatement');
        
    }
    
    public function assertDidNotDoUnprepared()
    {
        
        $this->assertDidNot('doUnprepared');
    }
    
    public function assertDidNotDoCursorSelect()
    {
        
        $this->assertDidNot('doCursorSelect');
    }
    
    public function doSelect(string $sql, array $bindings) :array
    {
        
        return $this->doQuery($sql, $bindings);
    }
    
    private function doQuery(string $sql, array $bindings)
    {
        
        $called_method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        
        $this->addToQueries($sql, $bindings, $called_method);
        
        $expectation = $this->return_values[$called_method] ?? null;
        
        if (is_null($expectation)) {
            PHPUnit::fail("No return value expectation set for method [$called_method]");
        }
        
        return $expectation instanceof Closure ? $expectation() : $expectation;
    }
    
    private function addToQueries(string $sql, array $bindings, string $called_method)
    {
        
        $this->queries[$called_method] = [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }
    
    public function doStatement(string $sql, array $bindings) :bool
    {
        
        return $this->doQuery($sql, $bindings);
    }
    
    public function doAffectingStatement($sql, array $bindings) :int
    {
        
        return $this->doQuery($sql, $bindings);
    }
    
    public function doUnprepared(string $sql) :bool
    {
        
        return $this->doQuery($sql, []);
        
    }
    
    public function doCursorSelect(string $sql, array $bindings) :mysqli_result
    {
        
        return $this->doQuery($sql, $bindings);
    }
    
    public function startTransaction()
    {
        //
    }
    
    public function commitTransaction()
    {
        //
    }
    
    public function rollbackTransaction(string $sql)
    {
        //
    }
    
    public function createSavepoint(string $sql)
    {
        //
    }
    
    public function returnInsert($value)
    {
        
        $this->return_values['doStatement'] = $value;
    }
    
    public function returnSelect($value)
    {
        
        $this->return_values['doSelect'] = $value;
    }
    
    public function returnUpdate($int)
    {
        
        $this->return_values['doAffectingStatement'] = $int;
    }
    
    public function returnDelete($int)
    {
        
        $this->return_values['doAffectingStatement'] = $int;
    }
    
    public function returnUnprepared($true)
    {
        
        $this->return_values['doUnprepared'] = $true;
    }
    
    public function returnCursor($result)
    {
        
        $this->return_values['doCursorSelect'] = $result;
    }
    
    public function lastInsertId() :int
    {
        
        $this->last_id++;
        
        return $this->last_id;
    }
    
}