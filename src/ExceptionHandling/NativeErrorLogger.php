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
            
            $entry = vsprintf(
                PHP_EOL.'[%s]: %s'.PHP_EOL.'File: [%s]'.PHP_EOL.'Line: [%s]'.PHP_EOL.'Trace:[%s]',
                [
                    $level,
                    $message.', '.get_class($e),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString(),
                ]
            );
            
        }
        
        error_log($entry);
        
    }
    
    private function getException(array $context) :?Throwable
    {
        
        $e = $context['exception'] ?? null;
        
        if ( ! $e instanceof Throwable) {
            return null;
        }
        
        return $e->getPrevious() instanceof Throwable
            ? $e->getPrevious()
            : $e;
        
    }
    
}