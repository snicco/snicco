<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\ValueObject\View;

/**
 * @internal
 */
final class UnsupportedDirectivesTest extends BladeTestCase
{
    /**
     * @test
     */
    public function service_injection_is_forbidden(): void
    {
        $view = $this->view('service');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@service was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @service directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function csrf_directive_throws_expection(): void
    {
        $view = $this->view('csrf');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@csrf was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @csrf directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_method_directive_throws(): void
    {
        $view = $this->view('method');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@method was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @method directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_auth_directive_throws(): void
    {
        $view = $this->view('auth');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@auth was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @auth directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_guest_directive_throws(): void
    {
        $view = $this->view('guest');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@guest was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @guest directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_production_directive_throws(): void
    {
        $view = $this->view('production');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@production was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @production directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_env_directive_throws(): void
    {
        $view = $this->view('env');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@env was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @env directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_can_directive_throws(): void
    {
        $view = $this->view('can');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@can was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @can directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_cannot_directive_throws(): void
    {
        $view = $this->view('cannot');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@cannot was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @cannot directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_canany_directive_throws(): void
    {
        $view = $this->view('canany');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@canany was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @canany directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_lang_directive_throws(): void
    {
        $view = $this->view('lang');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@lang was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @lang directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_choice_directive_throws(): void
    {
        $view = $this->view('choice');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@choice was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @choice directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_inject_directive_throws(): void
    {
        $view = $this->view('inject');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@inject was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @inject directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_error_directive_throws(): void
    {
        $view = $this->view('error');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@error was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @error directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_dd_directive_throws(): void
    {
        $view = $this->view('dd');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@dd was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @dd directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_dump_directive_throws(): void
    {
        $view = $this->view('dump');

        try {
            $this->view_engine->renderView($view);
            $this->fail('@dump was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The @dump directive is not supported as it requires the entire laravel framework.',
                (null !== $e->getPrevious()) ? $e->getPrevious()
                    ->getMessage() : $e->getMessage()
            );
        }
    }

    private function view(string $view): View
    {
        return $this->view_engine->make('blade-features.unsupported.' . $view);
    }
}
