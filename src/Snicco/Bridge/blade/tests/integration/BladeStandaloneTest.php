<?php

declare(strict_types=1);

namespace Tests\Blade\integration;

use Tests\Blade\BladeTestCase;

final class BladeStandaloneTest extends BladeTestCase
{
    
    /** @test */
    public function nested_views_can_be_rendered_relative_to_the_views_directory()
    {
        $view = $this->view_engine->make('nested.view');
        
        $this->assertViewContent('FOO', $view);
    }
    
    /** @test */
    public function the_first_available_view_can_be_created()
    {
        $first = $this->view_engine->make(['bogus1', 'bogus2', 'foo']);
        
        $this->assertViewContent('FOO', $first);
    }
    
}