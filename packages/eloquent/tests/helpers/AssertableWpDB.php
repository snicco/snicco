<?php

declare(strict_types=1);

namespace Tests\Database\helpers;

use wpdb;
use Snicco\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

use const ARRAY_A;

class AssertableWpDB
{
    
    /**
     * @var string
     */
    private $table;
    
    /**
     * @var wpdb
     */
    private $wpdb;
    
    public function __construct(string $table)
    {
        $this->table = $table;
        
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    public function assertRecordExists(array $column_conditions)
    {
        $wheres = '';
        $values = [];
        
        foreach ($column_conditions as $column => $value) {
            if (Str::endsWith($wheres, ['%f', '%d', '%s'])) {
                $wheres .= " AND ";
            }
            
            if (is_float($value)) {
                $wheres .= $column.' = %f';
            }
            
            if (is_int($value)) {
                $wheres .= $column.' = %d';
            }
            
            if (is_string($value)) {
                $wheres .= $column.' = %s';
            }
            
            $values[] = $value;
        }
        
        $query = $this->wpdb->prepare(
            "SELECT EXISTS(SELECT 1 FROM $this->table WHERE $wheres LIMIT 1)",
            $values
        );
        
        $exists = $this->wpdb->get_var($query);
        
        $result = (is_string($exists) && $exists === '1');
        
        $record_as_string = '';
        
        foreach ($column_conditions as $column => $value) {
            $record_as_string .= "$column => $value,";
        }
        
        $record_as_string = trim($record_as_string, ',');
        
        PHPUnit::assertTrue(
            $result,
            "The record [$record_as_string] was not found in the table [$this->table]."
        );
    }
    
    public function assertRecordNotExists(array $column_conditions)
    {
        [$wheres, $values] = $this->compile($column_conditions);
        
        $query = $this->wpdb->prepare(
            "SELECT EXISTS(SELECT 1 FROM $this->table WHERE $wheres LIMIT 1)",
            $values
        );
        
        $exists = $this->wpdb->get_var($query);
        
        $record_as_string = '';
        
        foreach ($column_conditions as $column => $value) {
            $record_as_string .= "$column => $value,";
        }
        
        $record_as_string = trim($record_as_string, ',');
        
        PHPUnit::assertSame(
            "0",
            $exists,
            "The record [$record_as_string] was unexpectedly found in the table [$this->table]."
        );
    }
    
    public function assertRecordEquals($conditions, array $expected)
    {
        [$wheres, $values] = $this->compile($conditions);
        
        $record = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM $this->table WHERE $wheres LIMIT 1", $values),
            ARRAY_A
        );
        
        PHPUnit::assertSame($expected, $record, 'The record does not exists as specified.');
    }
    
    public function assertTotalCount(int $int)
    {
        $query = "SELECT COUNT(*) FROM $this->table";
        
        $result = $this->wpdb->get_var($query);
        
        PHPUnit::assertSame(
            "$int",
            $result,
            "The expected count [$int] does not match the actual count [$result]."
        );
    }
    
    public function assertCountWhere(array $column_conditions, int $count)
    {
        [$wheres, $values] = $this->compile($column_conditions);
        
        $query = $this->wpdb->prepare("SELECT COUNT(*) FROM $this->table WHERE $wheres", $values);
        
        $actual_count = $this->wpdb->get_var($query);
        
        PHPUnit::assertSame(
            "$count",
            $actual_count,
            "The expected count [$count] does not match the actual count [$actual_count]."
        );
    }
    
    private function compile($conditions) :array
    {
        $wheres = '';
        $values = [];
        
        foreach ($conditions as $column => $value) {
            if (Str::endsWith($wheres, ['%f', '%d', '%s'])) {
                $wheres .= " AND ";
            }
            
            if (is_float($value)) {
                $wheres .= "`$column`".' = %f';
            }
            
            if (is_int($value)) {
                $wheres .= "`$column`".' = %d';
            }
            
            if (is_string($value)) {
                $wheres .= "`$column`".' = %s';
            }
            
            $values[] = $value;
        }
        
        return [$wheres, $values];
    }
    
}