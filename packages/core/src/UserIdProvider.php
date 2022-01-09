<?php

declare(strict_types=1);

namespace Snicco\Core;

interface UserIdProvider
{
    
    /**
     * Returns (int) 0 if no user is authenticated.
     *
     * @return int
     */
    public function currentUserID() :int;
    
}