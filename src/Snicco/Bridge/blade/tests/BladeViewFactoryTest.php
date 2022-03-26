<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;

/**
 * @internal
 */
final class BladeViewFactoryTest extends BladeTestCase
{
    /**
     * @test
     */
    public function the_blade_factory_can_create_and_render_a_view(): void
    {
        $view = $this->view_engine->make('foo');
        $this->assertSame('FOO', $this->view_engine->renderView($view));

        $view = $this->view_engine->make('foo.blade.php');
        $this->assertSame('FOO', $this->view_engine->renderView($view));
    }

    /**
     * @test
     */
    public function the_blade_factory_can_render_an_absolute_view(): void
    {
        $view = $this->view_engine->make(__DIR__ . '/fixtures/views/foo.blade.php');
        $this->assertSame('FOO', $this->view_engine->renderView($view));
    }

    /**
     * @test
     */
    public function that_the_correct_exception_is_thrown_for_missing_views(): void
    {
        $this->expectException(ViewNotFound::class);
        $this->view_engine->make('bogus');
    }

    /**
     * @test
     */
    public function that_nested_views_can_be_created_relative_to_the_views_directory(): void
    {
        $view = $this->view_engine->make('nested.view');
        $this->assertViewContent('FOO', $this->view_engine->renderView($view));
    }

    /**
     * @test
     */
    public function variables_can_be_shared_with_a_view(): void
    {
        $view = $this->view_engine->make('variables');
        $view = $view->with('name', 'calvin');

        $this->assertViewContent('hello calvin', $this->view_engine->renderView($view));
    }

    /**
     * @test
     */
    public function view_errors_are_caught(): void
    {
        $this->expectException(ViewCantBeRendered::class);

        $view = $this->view_engine->make('variables');
        $view = $view->with('bogus', 'calvin');

        $this->view_engine->renderView($view);
    }

    /**
     * @test
     */
    public function blade_internals_are_included_in_the_view(): void
    {
        $view = $this->view_engine->make('internal');

        $this->assertViewContent('app:env', $this->view_engine->renderView($view));
    }

    /**
     * @test
     */
    public function blade_views_can_be_rendered_from_a_string(): void
    {
        $html = $this->view_engine->render('variables', [
            'name' => 'calvin',
        ]);

        $this->assertViewContent('hello calvin', $html);
    }
}
