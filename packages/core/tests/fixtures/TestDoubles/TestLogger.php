<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\TestDoubles;

use Snicco\Support\Arr;
use Psr\Log\AbstractLogger;
use PHPUnit\Framework\Assert;

class TestLogger extends AbstractLogger
{
    
    private array $records  = [];
    private array $messages = [];
    private array $context  = [];
    
    public function log($level, $message, array $context = [])
    {
        $this->records[$level] = ['message' => $message, 'context' => $context];
        
        $this->messages[] = $message;
        $this->context[] = $context;
    }
    
    public function assertHasLogEntry($message, array $context = [])
    {
        Assert::assertContains($message, $this->messages);
        
        if ($context !== []) {
            Assert::assertContains($context, $this->context);
        }
    }
    
    public function assertHasNoLogEntries(string $level = null)
    {
        if ( ! $level) {
            Assert::assertSame([], $this->records);
            
            return;
        }
        
        Assert::assertEmpty($this->records[$level]);
    }
    
    public function assertHasLogLevelEntry(string $level, $message, array $context = [])
    {
        Assert::assertArrayHasKey(
            $level,
            $this->records,
            "The were no records logged for level [$level]."
        );
        
        $record = Arr::flattenOnePreserveKeys($this->records[$level]);
        
        Assert::assertSame($message, $record['message']);
        
        if ($context !== []) {
            Assert::assertSame($context, $record['context']);
        }
    }
    
}