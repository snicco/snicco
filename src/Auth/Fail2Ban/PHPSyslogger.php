<?php

namespace Snicco\Auth\Fail2Ban;

class PHPSyslogger implements Syslogger
{
    
    public function open(string $prefix, int $flags, int $facility) :bool
    {
        return openlog($prefix, $flags, $facility);
    }
    
    public function log(int $priority, string $message) :bool
    {
        return syslog($priority, $message);
    }
    
    public function close() :bool
    {
        return closelog();
    }
    
}