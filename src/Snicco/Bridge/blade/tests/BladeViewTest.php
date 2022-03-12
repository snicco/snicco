<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use RuntimeException;
use Snicco\Bridge\Blade\BladeView;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\View\View;

/**
 * @internal
 */
final class BladeViewTest extends BladeTestCase
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
        $view = $view->with('name', 'calvin');

        $this->assertViewContent('hello calvin', $view->render());
    }

    /**
     * @test
     */
    public function view_errors_are_caught(): void
    {
        $this->expectException(ViewCantBeRendered::class);

        $view = $this->view_engine->make('variables');
        $view = $view->with('bogus', 'calvin');

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
        $html = $this->view_engine->render('variables', [
            'name' => 'calvin',
        ]);

        $this->assertViewContent('hello calvin', $html);
    }

    /**
     * @test
     */
    public function a_blade_view_can_be_created_from_an_absolute_path(): void
    {
        $path = realpath($this->blade_views . '/foo.blade.php');

        if (false === $path) {
            throw new RuntimeException(sprintf('path [%s] does not exist.', $this->blade_views . '/foo.blade.php'));
        }

        $view = $this->view_engine->make($path);

        $this->assertInstanceOf(BladeView::class, $view);
        $this->assertInstanceOf(View::class, $view);
        $this->assertViewContent('FOO', $view->render());
    }

    /**
     * @test
     */
    public function test_with_is_immutable(): void
    {
        $view = $this->view_engine->make('variables');
        $view1 = $view->with('name', 'calvin');
        $html = $view1->render();
        $this->assertViewContent('hello calvin', $html);

        $view2 = $view1->with([
            'name' => 'marlon',
        ]);
        $html = $view2->render();
        $this->assertViewContent('hello marlon', $html);

        $html = $view1->render();
        $this->assertViewContent('hello calvin', $html);
    }

    /**
     * @test
     */
    public function test_path_and_name(): void
    {
        $view = $this->view_engine->make('foo');
        $this->assertSame('foo', $view->name());
        $this->assertSame(__DIR__ . '/fixtures/views/foo.blade.php', $view->path());
    }
}
