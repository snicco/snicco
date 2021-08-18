<?php

namespace Snicco\Auth\Fail2Ban;

use Snicco\Http\Psr7\Request;

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
     * @return string
     */
    public function fail2BanMessage() :string;
    
    public function request() :Request;
    
}