<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\stubs\TestApp;

class BladeLayoutsTest extends BladeTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function layouts_and_extending_work()
    {
        $view = TestApp::view('layouts.child');
        
        $this->assertViewContent(
            'Name:foo,SIDEBAR:parent_sidebar.appended,BODY:foobar,FOOTER:default_footer',
            $view->toString()
        );
    }
    
    /** @test */
    public function stacks_work()
    {
        $view = TestApp::view('layouts.stack-child');
        $content = $view->toString();
        $this->assertViewContent('FOOBAZBAR', $content);
    }
    
}