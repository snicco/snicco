<?php

declare(strict_types=1);

namespace Snicco\Core;

interface UserIdProvider
{
    
    /**
     * We don't return an integer because this allows us greater flexibility if we were to use
     * UUIDs for example.
     *
     * @return null|string
     */
    public function currentUserIdentifier() :?string;
    
}