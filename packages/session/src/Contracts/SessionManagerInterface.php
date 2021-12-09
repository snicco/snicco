<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

use Snicco\Core\Http\Cookie;
use Snicco\Session\Session;
use Snicco\Core\Http\Psr7\Request;

interface SessionManagerInterface
{
    
    public function start(Request $request, int $user_id) :Session;
    
    public function sessionCookie() :Cookie;
    
    public function save();
    
    public function collectGarbage();
    
}