<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Snicco\Bridge\Blade\BladeView;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\View\View;

class BladeViewTest extends BladeTestCase
{

    /**
     * @test
     */
    public function a_blade_view_can_be_rendered(): void
    {
        $view = $this->view_engine->make('foo');

        $this->assertInstanceOf(BladeView::class, $view);
        $this->assertInstanceOf(View::class, $view);
        $this->assertViewContent('FOO', $view->toString());
    }

    /**
     * @test
     */
    public function variables_can_be_shared_with_a_view(): void
    {
        $view = $this->view_engine->make('variables');
        $view->with('name', 'calvin');

        $this->assertViewContent('hello calvin', $view->toString());
    }

    /**
     * @test
     */
    public function view_errors_are_caught(): void
    {
        $this->expectException(ViewCantBeRendered::class);

        $view = $this->view_engine->make('variables');
        $view->with('bogus', 'calvin');

        $this->assertViewContent('hello calvin', $view->toString());
    }

    /**
     * @test
     */
    public function blade_internals_are_included_in_the_view(): void
    {
        $view = $this->view_engine->make('internal');

        $this->assertViewContent('app:env', $view->toString());
    }

    /**
     * @test
     */
    public function blade_views_can_be_rendered(): void
    {
        $html = $this->view_engine->render('variables', ['name' => 'calvin']);

        $this->assertViewContent('hello calvin', $html);
    }

    /**
     * @test
     */
    public function a_blade_view_can_be_created_from_an_absolute_path(): void
    {
        $path = realpath($this->blade_views . '/foo.blade.php');

        $view = $this->view_engine->make($path);

        $this->assertInstanceOf(BladeView::class, $view);
        $this->assertInstanceOf(View::class, $view);
        $this->assertViewContent('FOO', $view->toString());
    }

}