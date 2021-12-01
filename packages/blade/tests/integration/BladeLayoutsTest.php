<?php

declare(strict_types=1);

namespace Tests\Blade\integration;

use Tests\Blade\BladeTestCase;

class BladeLayoutsTest extends BladeTestCase
{
    
    /** @test */
    public function layouts_and_extending_work()
    {
        $view = $this->view_engine->make('layouts.child');
        
        $this->assertViewContent(
            'Name:foo,SIDEBAR:parent_sidebar.appended,BODY:foobar,FOOTER:default_footer',
            $view->toString()
        );
    }
    
    /** @test */
    public function stacks_work()
    {
        $view = $this->view_engine->make('layouts.stack-child');
        $content = $view->toString();
        $this->assertViewContent('FOOBAZBAR', $content);
    }
    
}