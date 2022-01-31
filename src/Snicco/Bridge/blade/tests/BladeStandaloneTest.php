<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

final class BladeStandaloneTest extends BladeTestCase
{
    
    /** @test */
    public function nested_views_can_be_rendered_relative_to_the_views_directory()
    {
        $view = $this->view_engine->make('nested.view');
        
        $this->assertViewContent('FOO', $view);
    }
    
}