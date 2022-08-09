<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Logger;

use Psr\Log\LoggerInterface;
use Snicco\Component\BetterWPCLI\Input\Input;
use Throwable;

final class PSRLogger implements Logger
{
    
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    public function logError(Throwable $e, string $command_name, Input $input) :void
    {
        $this->logger->critical("Uncaught exception while running command [$command_name]", [
            'exception' => $e,
        ]);
    }
    
    public function logCommandFailure(int $exit_code, string $command_name, Input $input) :void
    {
        $this->logger->warning("Command [$command_name] exited with status code [$exit_code]");
    }
    
}