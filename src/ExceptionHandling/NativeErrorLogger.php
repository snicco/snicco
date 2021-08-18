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
            
            $message .= ', '.get_class($e);
            $file = $e->getFile();
            $file = str_replace(ABSPATH, '/', $file);
            
            $message .= ", File: [$file], Line: [{$e->getLine()}]]";
            
        }
        
        error_log("[$level]: $message");
        
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