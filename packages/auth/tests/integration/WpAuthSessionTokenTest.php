<?php

declare(strict_types=1);

namespace Tests\Auth\integration;

use WP_User;
use WP_Session_Tokens;
use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Session\Contracts\SessionManagerInterface;

class WpAuthSessionTokenTest extends AuthTestCase
{
    
    private WP_User $user;
    
    protected function setUp() :void
    {
        $this->afterApplicationBooted(function () {
            $user = $this->createAdmin();
            $this->actingAs($user);
            $this->user = $user;
            $this->session_manager = TestApp::resolve(SessionManagerInterface::class);
        });
        
        parent::setUp();
        $this->bootApp();
    }
    
    public function testGetReturnsWpSessionInformation()
    {
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        
        $this->session_manager->save();
        
        $session = $instance->get($this->session->getId());
        
        $this->assertSame([
            'expiration' => 1000,
            'ip' => '127.0.0.1',
            'login' => time(),
        ], $session);
    }
    
    public function testSessionInformationCanBeAddedWithFilter()
    {
        add_filter('attach_session_information', function () {
            return ['foo' => 'bar'];
        });
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        
        $this->session_manager->save();
        
        $session = $instance->get($this->session->getId());
        
        $this->assertEquals([
            'expiration' => 1000,
            'ip' => '127.0.0.1',
            'login' => time(),
            'foo' => 'bar',
        ], $session);
    }
    
    public function testGetAllReturnsArrayOfSessions()
    {
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        
        $this->refreshSessionManager();
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        
        $expected = [
            [
                'expiration' => 1000,
                'ip' => '127.0.0.1',
                'login' => time(),
            ],
            [
                'expiration' => 1000,
                'ip' => '127.0.0.1',
                'login' => time(),
            ],
        ];
        
        $this->assertEquals($expected, $instance->get_all());
    }
    
    public function testVerify()
    {
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        
        $token = $this->session_manager->activeSession()->getId();
        
        $this->assertTrue($instance->verify($token));
    }
    
    public function testUpdate()
    {
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        $session = $instance->get($token = $this->session->getId());
        $this->assertSame([
            'expiration' => 1000,
            'ip' => '127.0.0.1',
            'login' => time(),
        ], $session);
        
        $instance->update($token, ['foo' => 'bar']);
        
        // Saving the session will always happen since its global middleware.
        $this->session_manager->save();
        
        // get new instance or session will be cached in memory
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $session = $instance->get($this->session->getId());
        $this->assertSame([
            'foo' => 'bar',
        ], $session);
    }
    
    public function testDestroy()
    {
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        
        $id = $this->session_manager->activeSession()->getId();
        
        $this->assertTrue($instance->verify($id));
        
        $instance->destroy($id);
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        
        $this->assertFalse($instance->verify($id));
    }
    
    public function testDestroyOthers()
    {
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        $first_id = $this->session_manager->activeSession()->getId();
        
        $this->refreshSessionManager();
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        $second_id = $this->session_manager->activeSession()->getId();
        
        $this->assertTrue($instance->verify($first_id));
        $this->assertTrue($instance->verify($second_id));
        
        $instance->destroy_others($first_id);
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        
        $this->assertTrue($instance->verify($first_id));
        $this->assertFalse($instance->verify($second_id));
    }
    
    public function testDestroyAllForUser()
    {
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        $first_id = $this->session_manager->activeSession()->getId();
        
        $this->refreshSessionManager();
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        $second_id = $this->session_manager->activeSession()->getId();
        
        $this->assertTrue($instance->verify($first_id));
        $this->assertTrue($instance->verify($second_id));
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->destroy_all();
        
        $this->assertFalse($instance->verify($first_id));
        $this->assertFalse($instance->verify($second_id));
    }
    
    public function testDestroyAll()
    {
        $instance = WP_Session_Tokens::get_instance($first_user_id = $this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        $first_id = $this->session_manager->activeSession()->getId();
        $this->assertTrue($instance->verify($first_id));
        
        $this->refreshSessionManager();
        
        $second_user = $this->createAdmin();
        $this->actingAs($second_user);
        $this->user = $second_user;
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $instance->create(1000);
        $this->session_manager->save();
        $second_id = $this->session_manager->activeSession()->getId();
        $this->assertTrue($instance->verify($second_id));
        
        WP_Session_Tokens::destroy_all_for_all_users();
        
        $instance = WP_Session_Tokens::get_instance($first_user_id);
        $this->assertFalse($instance->verify($first_id));
        
        $instance = WP_Session_Tokens::get_instance($this->user->ID);
        $this->assertFalse($instance->verify($second_id));
    }
    
}