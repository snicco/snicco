<?php

namespace Snicco\Database\Concerns;

use Closure;

trait LogsQueries
{
    
    /*
    |
    |
    |--------------------------------------------------------------------------
    | Pretending functions
    |--------------------------------------------------------------------------
    |
    | the pretend() method is used to well pretend that queries are run but instead
    | of actually hitting wpdb we print them to the terminal.
    | Used in the CLI scripts.
    |
    |
    |
    |
    */
    
    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param  Closure  $callback
     *
     * @return array
     */
    public function pretend(Closure $callback)
    {
        return $this->withFreshQueryLog(function () use ($callback) {
            $this->pretending = true;
            
            // Basically to make the database connection "pretend", we will just return
            // the default values for all the query methods, then we will return an
            // array of queries that were "executed" within the Closure callback.
            $callback($this);
            
            $this->pretending = false;
            
            return $this->query_log;
        });
    }
    
    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param  Closure  $callback
     *
     * @return array
     */
    private function withFreshQueryLog(Closure $callback) :array
    {
        $loggingQueries = $this->logging_queries;
        
        // First we will back up the value of the logging queries property and then
        // we'll be ready to run callbacks. This query log will also get cleared
        // so we will have a new log of all the queries that are executed now.
        $this->logging_queries = true;
        
        $this->query_log = [];
        
        // Now we'll execute this callback and capture the result. Once it has been
        // executed we will restore the value of query logging and give back the
        // value of the callback so the original callers can have the results.
        $result = $callback();
        
        $this->logging_queries = $loggingQueries;
        
        return $result;
    }
    
    /**
     * Log a query to the internal property if enabled.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  float  $time
     */
    private function logQuery(string $query, array $bindings, float $time)
    {
        $this->query_log[] = compact('query', 'bindings', 'time');
    }
    
    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int  $start
     *
     * @return float
     */
    private function getElapsedTime($start) :float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }
    
}