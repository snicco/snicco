<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Illuminate\Support\Facades\Blade;
use Snicco\Bridge\Blade\Tests\fixtures\Components\Alert;
use Snicco\Bridge\Blade\Tests\fixtures\Components\AlertAttributes;
use Snicco\Bridge\Blade\Tests\fixtures\Components\Dependency;
use Snicco\Bridge\Blade\Tests\fixtures\Components\HelloWorld;
use Snicco\Bridge\Blade\Tests\fixtures\Components\InlineComponent;
use Snicco\Bridge\Blade\Tests\fixtures\Components\ToUppercaseComponent;
use Snicco\Component\Templating\ValueObject\View;

/**
 * @internal
 */
final class BladeComponentsTest extends BladeTestCase
{
    /**
     * @test
     */
    public function basic_anonymous_components_work(): void
    {
        $view = $this->view_engine->make('anonymous-component');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello World', $content);
    }

    /**
     * @test
     */
    public function props_work_on_anonymous_components(): void
    {
        $view = $this->view_engine->make('anonymous-component-props');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('ID:props-component,CLASS:mt-4,MESSAGE:foo,TYPE:error', $content);
    }

    /**
     * @test
     */
    public function basic_class_based_components_work(): void
    {
        Blade::component(HelloWorld::class, 'hello-world');

        $view = $this->view_engine->make('class-component');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello World Class BladeComponent', $content);
    }

    /**
     * @test
     */
    public function class_components_can_pass_data(): void
    {
        Blade::component(Alert::class, 'alert');

        $view = $this->view_engine->make('alert-component');
        $view = $view->with('message', 'foo');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('TYPE:error,MESSAGE:foo', $content);

        $view = $this->view_engine->make('alert-component');
        $view = $view->with('message', 'FOO');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('TYPE:error,MESSAGE:COMPONENT METHOD CALLED', $content);
    }

    /**
     * @test
     */
    public function class_components_can_define_dependencies(): void
    {
        Blade::component(Dependency::class, 'with-dependency');

        $view = $this->view_engine->make('with-dependency-component');
        $view = $view->with('message', 'bar');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('MESSAGE:foobar', $content);
    }

    /**
     * @test
     */
    public function component_attributes_are_passed(): void
    {
        Blade::component(AlertAttributes::class, 'alert-attributes');

        $view = $this->view_engine->make('alert-attributes-component');
        $view = $view->with('message', 'foo');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('ID:alert-component,CLASS:mt-4,MESSAGE:foo,TYPE:error', $content);
    }

    /**
     * @test
     */
    public function components_attributes_have_priority_over_view_composers_and_globals(): void
    {
        Blade::component(AlertAttributes::class, 'alert-attributes');

        $this->composers->addComposer(
            'alert-attributes-component',
            fn (View $view): View => $view->with('message', 'bar')
        );

        $view = $this->view_engine->make('alert-attributes-component');
        $view = $view->with('message', 'foo');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('ID:alert-component,CLASS:mt-4,MESSAGE:foo,TYPE:error', $content);
    }

    /**
     * @test
     */
    public function slots_works(): void
    {
        Blade::component(ToUppercaseComponent::class, 'uppercase');

        $view = $this->view_engine->make('uppercase-component');
        $view = $view->with('content', 'foobar');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('TITLE:CALVIN,CONTENT:FOOBAR', $content);

        // with scope
        $view = $this->view_engine->make('uppercase-component');
        $view = $view->with([
            'content' => 'foobar',
            'scoped' => 'wordpress',
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('TITLE:CALVIN,CONTENT:FOOBAR,SCOPED:WORDPRESS', $content);
    }

    /**
     * @test
     */
    public function inline_components_work(): void
    {
        Blade::component(InlineComponent::class, 'inline');

        $view = $this->view_engine->make('inline-component');
        $view = $view->with('content', 'foobar');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Content:FOOBAR,SLOT:CALVIN', $content);
    }

    /**
     * @test
     */
    public function dynamic_components_work(): void
    {
        $view = $this->view_engine->make('dynamic-component');
        $view = $view->with('componentName', 'hello');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello World', $content);

        $view = $this->view_engine->make('dynamic-component');
        $view = $view->with('componentName', 'hello-calvin');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello Calvin', $content);
    }
}
