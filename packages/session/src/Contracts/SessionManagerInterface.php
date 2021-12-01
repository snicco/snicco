<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

use Snicco\Http\Cookie;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;

interface SessionManagerInterface
{
    
    public function start(Request $request, int $user_id) :Session;
    
    public function sessionCookie() :Cookie;
    
    public function save();
    
    public function collectGarbage();
    
}