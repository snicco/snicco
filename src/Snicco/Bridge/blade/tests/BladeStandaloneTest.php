<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Illuminate\Container\Container;
use Snicco\Bridge\Blade\DummyApplication;
use Illuminate\Contracts\Foundation\Application;

final class BladeStandaloneTest extends BladeTestCase
{
    
    /** @test */
    public function nested_views_can_be_rendered_relative_to_the_views_directory()
    {
        $view = $this->view_engine->make('nested.view');
        
        $this->assertViewContent('FOO', $view);
    }
    
    /** @test */
    public function a_dummy_application_is_put_into_the_container()
    {
        $this->assertInstanceOf(
            DummyApplication::class,
            Container::getInstance()->make(Application::class)
        );
    }
    
}