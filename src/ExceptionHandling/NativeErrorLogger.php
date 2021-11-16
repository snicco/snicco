<?php

namespace Snicco\ExceptionHandling;

use Throwable;
use Snicco\Support\Str;
use Psr\Log\AbstractLogger;

class NativeErrorLogger extends AbstractLogger
{
    
    private array $filter_frames;
    
    public function __construct(array $filter_frames = [])
    {
        $this->filter_frames = $filter_frames;
    }
    
    public function log($level, $message, array $context = [])
    {
        $message = strval($message);
        $level = strtoupper($level);
        
        if ($e = $this->getException($context)) {
            $has_previous = $e->getPrevious() instanceof Throwable;
            
            if ($has_previous) {
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
        return vsprintf(
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
                $this->filterTrace($original),
            ]
        );
    }
    
    private function formatWithoutPrevious(Throwable $e, string $level, string $log_message) :string
    {
        return vsprintf('[%s]: %s'.PHP_EOL.'File: [%s]'.PHP_EOL.'Line: [%s]'.PHP_EOL.'Trace:[%s]',
            [
                $level,
                $log_message.', '.get_class($e),
                $e->getFile(),
                $e->getLine(),
                $this->filterTrace($e),
            ]
        );
    }
    
    /**
     * This function allows us to filter repetitive lines in the stack trace that don't provide any
     * value. Common examples would be the Delegate.php or Middleware.php classes.
     *
     * @note The trace will not be filtered out if the error originated from within a filtered
     *     file.
     */
    private function filterTrace(Throwable $exception) :string
    {
        $trace_as_string = $exception->getTraceAsString();
        
        if (empty($this->filter_frames)) {
            return $trace_as_string;
        }
        
        $imploded = array_values(array_filter(explode('#', $trace_as_string)));
        
        foreach ($imploded as $key => $line) {
            if (Str::contains($line, $exception->getFile())) {
                continue;
            }
            
            if (Str::contains($line, $this->filter_frames)) {
                unset($imploded[$key]);
            }
        }
        
        $imploded = array_values($imploded);
        
        foreach ($imploded as $key => $line) {
            $imploded[$key] = "#$key /".Str::after($line, '/');
        }
        
        return implode('', $imploded);
    }
    
}