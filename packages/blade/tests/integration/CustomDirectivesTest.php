<?php

declare(strict_types=1);

namespace Tests\Blade\integration;

use Tests\Blade\BladeTestCase;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Testing\Concerns\InteractsWithWordpressUsers;

class CustomDirectivesTest extends BladeTestCase
{
    
    use InteractsWithWordpressUsers;
    
    protected function setUp() :void
    {
        parent::setUp();
        wp_logout();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        wp_logout();
    }
    
    /** @test */
    public function custom_auth_user_directive_works()
    {
        $calvin = $this->createAdmin();
        wp_set_current_user($calvin->ID);
        
        $view = $this->view('auth');
        $content = $view->toString();
        $this->assertViewContent('AUTHENTICATED', $content);
        
        wp_logout();
        
        $view = $this->view('auth');
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }
    
    /** @test */
    public function custom_guest_user_directive_works()
    {
        $view = $this->view('guest');
        $content = $view->toString();
        $this->assertViewContent('YOU ARE A GUEST', $content);
        
        $calvin = $this->createAdmin();
        wp_set_current_user($calvin->ID);
        
        $view = $this->view('guest');
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }
    
    /** @test */
    public function custom_wp_role_directives_work()
    {
        $admin = $this->createAdmin();
        wp_set_current_user($admin->ID);
        $view = $this->view('role');
        $content = $view->toString();
        $this->assertViewContent('ADMIN', $content);
        
        wp_logout();
        
        $editor = $this->createEditor();
        wp_set_current_user($editor->ID);
        $view = $this->view('role');
        $content = $view->toString();
        $this->assertViewContent('EDITOR', $content);
        
        $author = $this->createAuthor();
        wp_set_current_user($author->ID);
        $view = $this->view('role');
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }
    
    private function view(string $view) :ViewInterface
    {
        return $this->view_engine->make('blade-features.'.$view);
    }
    
}