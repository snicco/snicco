<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use RuntimeException;
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
        $this->assertViewContent('FOO', $view->render());
    }

    /**
     * @test
     */
    public function variables_can_be_shared_with_a_view(): void
    {
        $view = $this->view_engine->make('variables');
        $view->addContext('name', 'calvin');

        $this->assertViewContent('hello calvin', $view->render());
    }

    /**
     * @test
     */
    public function view_errors_are_caught(): void
    {
        $this->expectException(ViewCantBeRendered::class);

        $view = $this->view_engine->make('variables');
        $view->addContext('bogus', 'calvin');

        $this->assertViewContent('hello calvin', $view->render());
    }

    /**
     * @test
     */
    public function blade_internals_are_included_in_the_view(): void
    {
        $view = $this->view_engine->make('internal');

        $this->assertViewContent('app:env', $view->render());
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

        if (false === $path) {
            throw new RuntimeException("path [$path] does not exist.");
        }

        $view = $this->view_engine->make($path);

        $this->assertInstanceOf(BladeView::class, $view);
        $this->assertInstanceOf(View::class, $view);
        $this->assertViewContent('FOO', $view->render());
    }

    /**
     * @test
     */
    public function laravels_functions_are_implemented_correctly(): void
    {
        /** @var BladeView $view */
        $view = $this->view_engine->make('variables');

        $this->assertArrayNotHasKey('foo', $view->getData());

        $view->addContext('foo', 'bar');

        $this->assertArrayHasKey('foo', $view->getData());

        $this->assertSame(__DIR__ . '/fixtures/views/variables.blade.php', $view->path());
    }

    /**
     * @test
     */
    public function test_with_context_replaces_context(): void
    {
        $view = $this->view_engine->make('variables');
        $view->addContext('name', 'calvin');
        $html = $view->render();
        $this->assertViewContent('hello calvin', $html);

        $view->withContext(['name' => 'marlon']);
        $html = $view->render();
        $this->assertViewContent('hello marlon', $html);
    }

}