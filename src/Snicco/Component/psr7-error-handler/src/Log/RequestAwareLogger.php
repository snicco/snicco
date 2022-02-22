<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Log;

use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Throwable;

final class RequestAwareLogger
{

    private LoggerInterface $psr_logger;

    /**
     * @var RequestLogContext[]
     */
    private array $context;

    /**
     * @var array<class-string<Throwable>,string>
     */
    private array $log_levels = [];

    /**
     * @param array<class-string<Throwable>,string> $log_levels
     */
    public function __construct(LoggerInterface $psr_logger, array $log_levels = [], RequestLogContext ...$context)
    {
        $this->psr_logger = $psr_logger;
        $this->context = $context;
        foreach ($log_levels as $class => $log_level) {
            $this->addLogLevel($class, $log_level);
        }
    }

    public function log(ExceptionInformation $exception_information, RequestInterface $request): void
    {
        $context = [
            'exception' => $e = $exception_information->originalException(),
            'identifier' => $exception_information->identifier(),
        ];

        foreach ($this->context as $request_context) {
            $context = $request_context->add($context, $request, $exception_information);
        }

        $this->psr_logger->log(
            $this->determineLogLevel($e, $exception_information->statusCode()),
            $e->getMessage(),
            $context
        );
    }

    private function addLogLevel(string $class, string $log_level): void
    {
        $this->log_levels[$class] = $log_level;
    }

    private function determineLogLevel(Throwable $e, int $status_code): string
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

        return $status_code >= 500 ? LogLevel::CRITICAL : LogLevel::ERROR;
    }

}