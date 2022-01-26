<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler\Log;

use Throwable;
use Exception;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestInterface;

final class RequestAwareLogger
{
    
    private LoggerInterface $psr_logger;
    
    /**
     * @var RequestContext[]
     */
    private array $context;
    
    /**
     * @var array<string,string>
     */
    private array $log_levels = [];
    
    /**
     * @param  array<string,string>  $log_levels
     */
    public function __construct(LoggerInterface $psr_logger, array $log_levels = [], RequestContext ...$context)
    {
        $this->psr_logger = $psr_logger;
        $this->context = $context;
        foreach ($log_levels as $class => $log_level) {
            $this->addLogLevel($class, $log_level);
        }
    }
    
    public function log(Throwable $e, RequestInterface $request, string $exception_identifier) :void
    {
        $context = ['exception' => $e, 'identifier' => $exception_identifier];
        
        $this->psr_logger->log(
            $this->determineLogLevel($e),
            $e->getMessage(),
            $context
        );
    }
    
    private function addLogLevel(string $class, string $log_level) :void
    {
        $this->log_levels[$class] = $log_level;
    }
    
    private function determineLogLevel(Throwable $e) :string
    {
        $user_defined_level = null;
        foreach ($this->log_levels as $type => $level) {
            if ($e instanceof $type) {
                $user_defined_level = $level;
                break;
            }
        }
        
        if ($user_defined_level) {
            return $user_defined_level;
        }
        
        if ($e instanceof Exception) {
            return LogLevel::ERROR;
        }
        
        return LogLevel::CRITICAL;
    }
    
}