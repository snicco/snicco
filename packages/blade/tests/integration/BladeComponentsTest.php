<?php

declare(strict_types=1);

namespace Tests\Blade\integration;

use Tests\Blade\BladeTestCase;
use Illuminate\Support\Facades\Blade;
use Tests\Blade\fixtures\Components\Alert;
use Tests\Blade\fixtures\Components\HelloWorld;
use Tests\Blade\fixtures\Components\Dependency;
use Tests\Blade\fixtures\Components\InlineComponent;
use Tests\Blade\fixtures\Components\AlertAttributes;
use Tests\Blade\fixtures\Components\ToUppercaseComponent;

class BladeComponentsTest extends BladeTestCase
{
    
    /** @test */
    public function basic_anonymous_components_work()
    {
        $view = $this->view_engine->make('anonymous-component');
        $content = $view->toString();
        $this->assertViewContent('Hello World', $content);
    }
    
    /** @test */
    public function props_work_on_anonymous_components()
    {
        $view = $this->view_engine->make('anonymous-component-props');
        $content = $view->toString();
        $this->assertViewContent('ID:props-component,CLASS:mt-4,MESSAGE:foo,TYPE:error', $content);
    }
    
    /** @test */
    public function basic_class_based_components_work()
    {
        Blade::component(HelloWorld::class, 'hello-world');
        
        $view = $this->view_engine->make('class-component');
        $content = $view->toString();
        $this->assertViewContent('Hello World Class BladeComponent', $content);
    }
    
    /** @test */
    public function class_components_can_pass_data()
    {
        Blade::component(Alert::class, 'alert');
        
        $view = $this->view_engine->make('alert-component')->with('message', 'foo');
        $content = $view->toString();
        $this->assertViewContent('TYPE:error,MESSAGE:foo', $content);
        
        $view = $this->view_engine->make('alert-component')->with('message', 'FOO');
        $content = $view->toString();
        $this->assertViewContent('TYPE:error,MESSAGE:COMPONENT METHOD CALLED', $content);
    }
    
    /** @test */
    public function class_components_can_define_dependencies()
    {
        Blade::component(Dependency::class, 'with-dependency');
        
        $view = $this->view_engine->make('with-dependency-component')->with('message', 'bar');
        $content = $view->toString();
        $this->assertViewContent('MESSAGE:foobar', $content);
    }
    
    /** @test */
    public function component_attributes_are_passed()
    {
        Blade::component(AlertAttributes::class, 'alert-attributes');
        
        $view = $this->view_engine->make('alert-attributes-component')->with('message', 'foo');
        $content = $view->toString();
        $this->assertViewContent('ID:alert-component,CLASS:mt-4,MESSAGE:foo,TYPE:error', $content);
    }
    
    /** @test */
    public function slots_works()
    {
        Blade::component(ToUppercaseComponent::class, 'uppercase');
        
        $view = $this->view_engine->make('uppercase-component')->with('content', 'foobar');
        $content = $view->toString();
        $this->assertViewContent('TITLE:CALVIN,CONTENT:FOOBAR', $content);
        
        // with scope
        $view = $this->view_engine->make('uppercase-component')->with([
            'content' => 'foobar',
            'scoped' => 'wordpress',
        ]);
        $content = $view->toString();
        $this->assertViewContent('TITLE:CALVIN,CONTENT:FOOBAR,SCOPED:WORDPRESS', $content);
    }
    
    /** @test */
    public function inline_components_work()
    {
        Blade::component(InlineComponent::class, 'inline');
        
        $view = $this->view_engine->make('inline-component')->with('content', 'foobar');
        $content = $view->toString();
        $this->assertViewContent('Content:FOOBAR,SLOT:CALVIN', $content);
    }
    
    /** @test */
    public function dynamic_components_work()
    {
        $view = $this->view_engine->make('dynamic-component')->with('componentName', 'hello');
        $content = $view->toString();
        $this->assertViewContent('Hello World', $content);
        
        $view =
            $this->view_engine->make('dynamic-component')->with('componentName', 'hello-calvin');
        $content = $view->toString();
        $this->assertViewContent('Hello Calvin', $content);
    }
    
}