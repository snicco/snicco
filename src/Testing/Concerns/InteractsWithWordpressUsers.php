<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use WP_User;
use PHPUnit\Framework\Assert as PHPUnit;

trait InteractsWithWordpressUsers
{
    
    protected function createAdmin(array $args = []) :WP_User
    {
        return $this->createUserWithRole('administrator', $args);
    }
    
    protected function createUserWithRole(string $user_role, array $args = []) :WP_User
    {
        return $this->factory()->user->create_and_get(
            array_merge([
                'role' => $user_role,
            ], $args)
        );
    }
    
    protected function createEditor(array $args = []) :WP_User
    {
        return $this->createUserWithRole('editor', $args);
    }
    
    protected function createAuthor(array $args = []) :WP_User
    {
        return $this->createUserWithRole('author', $args);
    }
    
    protected function createSubscriber(array $args = []) :WP_User
    {
        return $this->createUserWithRole('subscriber', $args);
    }
    
    protected function assertUserDeleted($user)
    {
        $user_id = $this->normalizeUser($user);
        
        $user = get_user_by('id', $user_id);
        
        PHPUnit::assertNotInstanceOf(WP_User::class, $user, "The user [$user_id] still exists.");
    }
    
    protected function assertUserNotDeleted($user)
    {
        $user_id = $this->normalizeUser($user);
        
        $user = get_user_by('id', $user_id);
        
        PHPUnit::assertInstanceOf(WP_User::class, $user, "The user [$user_id] does not exists.");
    }
    
    private function normalizeUser($user) :int
    {
        return $user instanceof WP_User ? $user->ID : $user;
    }
    
}