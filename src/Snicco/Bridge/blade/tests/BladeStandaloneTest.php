<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Snicco\Bridge\Blade\DummyApplication;

final class BladeStandaloneTest extends BladeTestCase
{

    /**
     * @test
     */
    public function nested_views_can_be_rendered_relative_to_the_views_directory(): void
    {
        $view = $this->view_engine->make('nested.view');

        $this->assertViewContent('FOO', $view->toString());
    }

    /**
     * @test
     */
    public function a_dummy_application_is_put_into_the_container(): void
    {
        $this->assertInstanceOf(
            DummyApplication::class,
            Container::getInstance()->make(Application::class)
        );
    }

}