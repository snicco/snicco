<?php

declare(strict_types=1);

namespace Snicco\Auth\Traits;

use WP_User;

trait ResolvesUser
{
    
    protected function getUserByLogin(string $login)
    {
        $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);
        
        return $is_email
            ? get_user_by('email', trim(wp_unslash($login)))
            : get_user_by('login', trim(wp_unslash($login)));
    }
    
    protected function getUserById($id) :?WP_User
    {
        $user = get_user_by('id', (int) $id);
        
        return $user instanceof WP_User ? $user : null;
    }
    
    protected function isAdmin(WP_User $user) :bool
    {
        return $this->userIs($user, 'administrator');
    }
    
    protected function userIs(WP_User $user, string $role) :bool
    {
        $roles = $user->roles;
        
        return in_array($role, $roles, true);
    }
    
}