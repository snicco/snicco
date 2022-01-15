<?php

namespace Snicco\Auth\Fail2Ban;

use PHPUnit\Framework\Assert;

class TestSysLogger implements Syslogger
{
    
    private array $opened_log  = [];
    private array $log_entries = [];
    private bool  $log_closed  = false;
    
    public function open(string $prefix, int $flags, int $facility) :bool
    {
        $this->opened_log = ['prefix' => $prefix, 'flags' => $flags, 'facility' => $facility];
        return true;
    }
    
    public function log(int $priority, string $message) :bool
    {
        $this->log_entries[] = $priority.'-'.$message;
        return true;
    }
    
    public function close() :bool
    {
        $this->log_closed = true;
        return $this->log_closed;
    }
    
    public function assertLogOpened()
    {
        Assert::assertNotEmpty($this->opened_log, "Log was not opened.");
    }
    
    public function assertLogOpenedWithPrefix(string $prefix)
    {
        Assert::assertSame(
            $prefix,
            $this->opened_log['prefix'],
            "Log not opened with prefix [$prefix]."
        );
    }
    
    public function assertLogOpenedWithFlags(int $flags)
    {
        Assert::assertSame(
            $flags,
            $this->opened_log['flags'],
            "Log not opened with flags [$flags]."
        );
    }
    
    public function assertLogOpenedForFacility(int $facility)
    {
        Assert::assertSame(
            $facility,
            $this->opened_log['facility'],
            "Log not opened with facility [$facility]."
        );
    }
    
    public function assertLogEntry(int $priority, string $message)
    {
        Assert::assertContains($priority.'-'.$message, $this->log_entries);
    }
    
}