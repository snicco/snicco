<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use WP_User;
use Snicco\Component\ScopableWP\WPApi;

use function is_user_logged_in;
use function wp_get_current_user;

/**
 * @api
 * @final
 */
class ScopableWP extends WPApi
{
    
    public function isUserLoggedIn() :bool
    {
        return is_user_logged_in();
    }
    
    public function getCurrentUser() :WP_User
    {
        return wp_get_current_user();
    }
    
}