<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Mockery;
use WP_User;
use Snicco\Bridge\Blade\ScopableWP;
use Snicco\Component\Templating\View\View;

class CustomDirectivesTest extends BladeTestCase
{
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }
    
    /** @test */
    public function custom_auth_user_directive_works()
    {
        $wp = Mockery::mock(ScopableWP::class);
        $wp->shouldReceive('isUserLoggedIn')->once()->andReturnTrue();
        $this->blade->bindWordPressDirectives($wp);
        
        $view = $this->view('auth');
        $content = $view->toString();
        $this->assertViewContent('AUTHENTICATED', $content);
        
        $wp->shouldReceive('isUserLoggedIn')->once()->andReturnFalse();
        
        $view = $this->view('auth');
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }
    
    /** @test */
    public function custom_guest_user_directive_works()
    {
        $wp = Mockery::mock(ScopableWP::class);
        $wp->shouldReceive('isUserLoggedIn')->once()->andReturnFalse()->byDefault();
        $this->blade->bindWordPressDirectives($wp);
        
        $view = $this->view('guest');
        $content = $view->toString();
        $this->assertViewContent('YOU ARE A GUEST', $content);
        
        $wp->shouldReceive('isUserLoggedIn')->once()->andReturnTrue();
        
        $view = $this->view('guest');
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }
    
    /** @test */
    public function custom_wp_role_directives_work()
    {
        $wp = Mockery::mock(ScopableWP::class);
        $this->blade->bindWordPressDirectives($wp);
        
        $user1 = Mockery::mock(WP_User::class);
        $user1->roles = ['administrator'];
        // Twice because we use the role directive twice in the view.
        $wp->shouldReceive('getCurrentUser')->twice()->andReturn($user1);
        
        $view = $this->view('role');
        $content = $view->toString();
        $this->assertViewContent('ADMIN', $content);
        
        $user2 = Mockery::mock(WP_User::class);
        $user2->roles = ['editor'];
        $wp->shouldReceive('getCurrentUser')->twice()->andReturn($user2);
        
        $view = $this->view('role');
        $content = $view->toString();
        $this->assertViewContent('EDITOR', $content);
        
        $user3 = Mockery::mock(WP_User::class);
        $user3->roles = ['author'];
        $wp->shouldReceive('getCurrentUser')->twice()->andReturn($user3);
        
        $view = $this->view('role');
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }
    
    private function view(string $view) :View
    {
        return $this->view_engine->make('blade-features.'.$view);
    }
    
}