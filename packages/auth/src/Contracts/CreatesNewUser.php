<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use WP_User;
use Snicco\Http\Psr7\Request;

interface CreatesNewUser
{
    
    /**
     * Validate and create a new WP_User for the given request.
     *
     * @param  Request  $request
     *
     * @return WP_User The new user.
     */
    public function create(Request $request) :WP_User;
    
}