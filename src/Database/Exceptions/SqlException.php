<?php

declare(strict_types=1);

namespace Snicco\Database\Exceptions;

use Throwable;
use Illuminate\Support\Str;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class SqlException extends HttpException
{
    
    private string $sql;
    private array  $bindings;
    
    public function __construct(string $sql, array $bindings = [], Throwable $previous = null)
    {
        
        $this->sql = $sql;
        $this->bindings = $bindings;
        
        parent::__construct(
            500,
            $this->formatMessage($sql, $bindings, $previous),
            $previous
        );
    }
    
    public function getSql() :string
    {
        return $this->sql;
    }
    
    public function getBindings() :array
    {
        return $this->bindings;
    }
    
    private function formatMessage(string $sql, array $bindings, Throwable $previous = null) :string
    {
        $prev = ! is_null($previous) ? $previous->getMessage() : '';
        return $prev.' (SQL: '.Str::replaceArray('?', $bindings, $sql).')';
    }
    
}