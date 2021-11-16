<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\Contracts\Responsable;

interface DeletesUsers
{
    
    /**
     * Return a non-null value to reassign the users posts to the returned user id
     *
     * @param  int  $user_to_be_deleted
     *
     * @return int|null
     */
    public function reassign(int $user_to_be_deleted) :?int;
    
    /**
     * An array of WordPress user roles that should have permissions to delete THEIR OWN ACCOUNT.
     * Admins are able to delete all user roles.
     *
     * @return array
     */
    public function allowedUserRoles() :array;
    
    /**
     * This function will be called if the request was not an ajax request and can
     * be used to redirect the user to a survey or thank you page.
     *
     * @return Responsable
     */
    public function response() :Responsable;
    
    /**
     * Perform actions before the user is deleted.
     *
     * @param  int  $user_id
     */
    public function preDelete(int $user_id) :void;
    
    /**
     * Perform actions after the user is deleted.
     *
     * @param  int  $user_id
     */
    public function postDelete(int $user_id) :void;
    
}