<?php

declare(strict_types=1);

namespace Tests\Auth\integration;

use WP_User;
use Snicco\Session\Session;
use Snicco\Session\Contracts\SessionDriver;
use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\Contracts\SessionManagerInterface;

class AuthSessionManagerTest extends AuthTestCase
{
    
    private ArraySessionDriver $driver;
    
    protected function setUp() :void
    {
        $this->afterApplicationBooted(function () {
            $this->driver = TestApp::resolve(SessionDriver::class);
            $this->session_manager = TestApp::resolve(SessionManagerInterface::class);
            $this->withRequest($this->frontendRequest('GET', '/foo'));
        });
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function all_sessions_for_the_current_user_can_be_retrieved()
    {
        $calvin = $this->createAdmin();
        $this->seedSessions($calvin, 2);
        
        $john = $this->createAdmin();
        $this->seedSessions($john, 1);
        
        $this->refreshSessionManager();
        
        $this->actingAs($calvin);
        
        $this->session_manager->start($this->request, $calvin->ID);
        $sessions = $this->session_manager->getAllForUser();
        
        // Sessions from john are not included.
        $this->assertCount(2, $sessions);
        
        $this->actingAs($john);
        $this->refreshSessionManager();
        $this->session_manager->start($this->request, $john->ID);
        $sessions = $this->session_manager->getAllForUser();
        
        // Sessions from calvin are not included.
        $this->assertCount(1, $sessions);
    }
    
    /** @test */
    public function all_sessions_for_the_current_user_can_be_destroyed()
    {
        $calvin = $this->createAdmin();
        $this->seedSessions($calvin, 2);
        
        $john = $this->createAdmin();
        $this->seedSessions($john, 2);
        
        $this->refreshSessionManager();
        
        $this->actingAs($calvin);
        $this->session_manager->start($this->request, $calvin->ID);
        $this->session_manager->destroyAllForUser($calvin->ID);
        
        // Calvin's session are gone.
        $this->assertCount(0, $this->session_manager->getAllForUser());
        
        $this->actingAs($john);
        $this->refreshSessionManager();
        $this->session_manager->start($this->request, $john->ID);
        
        // Johns session still there.
        $this->assertCount(2, $this->session_manager->getAllForUser());
    }
    
    /** @test */
    public function all_other_sessions_for_the_current_user_can_be_destroyed()
    {
        $calvin = $this->createAdmin();
        $this->seedSessions($calvin, 3);
        
        $this->assertCount(3, $this->session_manager->getAllForUser());
        
        $token = $this->hash($this->activeSession()->getId());
        
        $this->session_manager->destroyOthersForUser($token, $calvin->ID);
        
        $this->assertCount(1, $this->session_manager->getAllForUser());
    }
    
    /** @test */
    public function all_sessions_can_be_destroyed()
    {
        $calvin = $this->createAdmin();
        $john = $this->createAdmin();
        
        $this->seedSessions($calvin, 2);
        $this->seedSessions($john, 2);
        
        $this->assertCount(4, $this->driver->all());
        
        $this->session_manager->destroyAll();
        
        $this->assertCount(0, $this->driver->all());
    }
    
    /** @test */
    public function expired_sessions_are_not_included()
    {
        // Ensure this test does not fail because of the session being idle.
        $this->withAddedConfig('auth.idle', 1000000);
        
        $calvin = $this->createAdmin();
        $this->seedSessions($calvin, 1);
        
        $this->travelIntoFuture(7200);
        
        $this->assertCount(
            1,
            $this->session_manager->getAllForUser(),
            'Session was deleted when it was not timed-out absolutely.'
        );
        
        $this->travelIntoFuture(1);
        
        $this->assertCount(0, $this->session_manager->getAllForUser());
    }
    
    /** @test */
    public function idle_sessions_are_included_if_persistent_login_is_enabled()
    {
        $this->withAddedConfig('auth.features.remember_me', true);
        
        $calvin = $this->createAdmin();
        $this->seedSessions($calvin, 1);
        
        $this->travelIntoFuture(1800);
        
        $this->assertCount(1, $this->session_manager->getAllForUser());
        
        $this->travelIntoFuture(1);
        
        // The session is idle but we dont invalidate it.
        // This happens in another middleware where auth confirmation is deleted.
        $this->assertCount(1, $this->session_manager->getAllForUser());
    }
    
    /** @test */
    public function idle_sessions_are_not_included_if_remember_me_is_disabled()
    {
        $calvin = $this->createAdmin();
        $this->seedSessions($calvin, 1);
        
        $this->travelIntoFuture(1800);
        
        $this->assertCount(1, $this->session_manager->getAllForUser());
        
        $this->travelIntoFuture(1);
        
        $this->assertCount(0, $this->session_manager->getAllForUser());
    }
    
    /** @test */
    public function the_idle_timeout_can_be_customized_at_runtime()
    {
        $calvin = $this->createAdmin();
        $this->seedSessions($calvin, 1);
        
        $this->session_manager->setIdleResolver(function ($idle) {
            return $idle - 1;
        });
        
        $this->travelIntoFuture(1800);
        
        $this->assertCount(0, $this->session_manager->getAllForUser());
    }
    
    private function seedSessions(WP_User $user, int $count)
    {
        $created = 0;
        
        $this->actingAs($user);
        
        while ($created < $count) {
            $this->refreshSessionManager();
            
            $this->session_manager->start($this->request, $user->ID);
            $this->session_manager->save();
            
            $created++;
        }
    }
    
    private function activeSession() :Session
    {
        return $this->session_manager->activeSession();
    }
    
}