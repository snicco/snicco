<?php

namespace Snicco\ExceptionHandling;

use Throwable;
use Psr\Log\AbstractLogger;

class NativeErrorLogger extends AbstractLogger
{
    
    // This will log to default log file which is configured by WordPress and is by default
    // placed inside /wp-content.
    // Make sure WP_DEBUG_LOG is set to true in wp-config.php
    public function log($level, $message, array $context = [])
    {
        
        $message = strval($message);
        $level = strtoupper($level);
        
        if ($e = $this->getException($context)) {
            
            $prev = $e->getPrevious() instanceof Throwable;
            
            if ($prev) {
                
                $entry = $this->formatPrevious($e->getPrevious(), $e, $level, $message);
                
            }
            else {
                $entry = $this->formatWithoutPrevious($e, $level, $message);
            }
            
        }
        else {
            $entry = "[$level]: $message";
        }
        
        error_log($entry);
        
    }
    
    private function getException(array $context) :?Throwable
    {
        
        $e = $context['exception'] ?? null;
        
        if ( ! $e instanceof Throwable) {
            return null;
        }
        
        return $e;
        
    }
    
    private function formatPrevious(Throwable $previous, Throwable $original, string $level, string $log_message) :string
    {
        
        $entry = vsprintf(
            PHP_EOL
            .'[%s]: %s'
            .PHP_EOL
            ."\tCaused by: [%s] %s"
            .PHP_EOL
            ."\tFile: [%s]"
            .PHP_EOL
            ."\tLine: [%s]"
            .PHP_EOL
            .'File: [%s]'
            .PHP_EOL
            .'Line: [%s]'
            .PHP_EOL
            .'Trace:[%s]',
            [
                $level,
                $log_message.', '.get_class($original),
                get_class($previous),
                $previous->getMessage(),
                $previous->getFile(),
                $previous->getLine(),
                $original->getFile(),
                $original->getLine(),
                $original->getTraceAsString(),
            ]
        );
        
        return $entry;
        
    }
    
    private function formatWithoutPrevious(Throwable $e, string $level, string $log_message) :string
    {
        
        $entry = vsprintf('[%s]: %s'.PHP_EOL.'File: [%s]'.PHP_EOL.'Line: [%s]'.PHP_EOL.'Trace:[%s]',
            [
                $level,
                $log_message.', '.get_class($e),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString(),
            ]
        );
        
        return $entry;
        
    }
    
}