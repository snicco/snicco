<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use WP_User;
use Snicco\Http\Controller;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Events\UserDeleted;
use Snicco\Auth\Events\Registration;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\DeletesUsers;
use Snicco\Auth\Contracts\CreatesNewUser;
use Snicco\Auth\Contracts\CreateAccountView;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Auth\Contracts\AbstractRegistrationResponse;
use Snicco\ExceptionHandling\Exceptions\AuthorizationException;

class AccountController extends Controller
{
    
    use ResolvesUser;
    
    private int        $lifetime_in_seconds;
    private Dispatcher $events;
    
    public function __construct(Dispatcher $events, $lifetime_in_seconds = 900)
    {
        $this->events = $events;
        $this->lifetime_in_seconds = $lifetime_in_seconds;
    }
    
    public function create(Request $request, CreateAccountView $view_response)
    {
        return $view_response->forRequest($request)->postTo(
            $this->url->signedRoute('auth.accounts.store', [], $this->lifetime_in_seconds)
        );
    }
    
    public function store(Request $request, CreatesNewUser $creates_new_user, AbstractRegistrationResponse $response)
    {
        $user = $creates_new_user->create($request);
        
        $this->events->dispatch(new Registration($user));
        
        return $response->forRequest($request)->forUser($user);
    }
    
    public function destroy(Request $request, DeletesUsers $deletes_users, int $user_id)
    {
        $allowed_roles = array_merge(['administrator'], $deletes_users->allowedUserRoles());
        
        if ( ! $this->canUserPerformDelete($allowed_roles, $request->user(), $user_id)) {
            throw new AuthorizationException(
                "Account deletion attempt with insufficient permissions for user_id [$user_id]"
            );
        }
        
        // Isn't WordPress great?
        if ( ! function_exists('wp_delete_user')) {
            require ABSPATH.'wp-admin/includes/user.php';
        }
        
        $deletes_users->preDelete($user_id);
        
        wp_delete_user($user_id, $deletes_users->reassign($user_id));
        
        $deletes_users->postDelete($user_id);
        
        $this->events->dispatch(new UserDeleted($user_id));
        
        return $request->isExpectingJson()
            ? $this->response_factory->noContent()
            : $deletes_users->response();
    }
    
    private function canUserPerformDelete(array $allowed_roles, WP_User $auth_user, int $delete_id) :bool
    {
        $user_to_be_delete = $this->getUserById($delete_id);
        $current_user_is_admin = $this->isAdmin($auth_user);
        
        // Only whitelisted roles can be deleted. Admins can delete all roles expect other admins/super admins
        if ( ! $current_user_is_admin
             && ! count(
                array_intersect($user_to_be_delete->roles, $allowed_roles)
            )) {
            return false;
        }
        
        // Never allow admin/super admin account deletion
        if ($this->isAdmin($user_to_be_delete) || is_super_admin($delete_id)) {
            return false;
        }
        
        // Don't allow deletion of accounts that are not the users own account
        if ($auth_user->ID !== $delete_id && ! $current_user_is_admin) {
            return false;
        }
        
        return true;
    }
    
}