<?php

namespace Snicco\Contracts;

interface Bannable
{
    
    /**
     * Return one of PHPs native log levels
     *
     * @return int
     */
    public function priority() :int;
    
    /**
     * The message that should be logged with fail2ban.*
     *
     * @return int
     */
    public function fail2BanMessage();
    
}