<?php

namespace Snicco\Auth\Fail2Ban;

interface Syslogger
{
    
    /**
     * Open a connection to the system logger
     *
     * @param  string  $prefix
     * @param  int  $flags
     * @param  int  $facility
     *
     * @return bool true on success or false on failure.
     */
    public function open(string $prefix, int $flags, int $facility) :bool;
    
    /**
     * Log an entry to the system logger
     *
     * @param  int  $priority
     * @param  string  $message
     *
     * @return bool true on success or false on failure.
     */
    public function log(int $priority, string $message) :bool;
    
    /**
     * Close a connection to the system logger
     *
     * @return bool true on success or false on failure.
     */
    public function close() :bool;
    
}