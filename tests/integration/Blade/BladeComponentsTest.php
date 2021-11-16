<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\stubs\TestApp;
use Illuminate\Support\Facades\Blade;
use Tests\integration\Blade\Components\Alert;
use Tests\integration\Blade\Components\HelloWorld;
use Tests\integration\Blade\Components\Dependency;
use Tests\integration\Blade\Components\InlineComponent;
use Tests\integration\Blade\Components\AlertAttributes;
use Tests\integration\Blade\Components\ToUppercaseComponent;

class BladeComponentsTest extends BladeTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function basic_anonymous_components_work()
    {
        $view = TestApp::view('anonymous-component');
        $content = $view->toString();
        $this->assertViewContent('Hello World', $content);
    }
    
    /** @test */
    public function props_work_on_anonymous_components()
    {
        $view = TestApp::view('anonymous-component-props');
        $content = $view->toString();
        $this->assertViewContent('ID:props-component,CLASS:mt-4,MESSAGE:foo,TYPE:error', $content);
    }
    
    /** @test */
    public function basic_class_based_components_work()
    {
        Blade::component(HelloWorld::class, 'hello-world');
        
        $view = TestApp::view('class-component');
        $content = $view->toString();
        $this->assertViewContent('Hello World Class BladeComponent', $content);
    }
    
    /** @test */
    public function class_components_can_pass_data()
    {
        Blade::component(Alert::class, 'alert');
        
        $view = TestApp::view('alert-component')->with('message', 'foo');
        $content = $view->toString();
        $this->assertViewContent('TYPE:error,MESSAGE:foo', $content);
        
        $view = TestApp::view('alert-component')->with('message', 'FOO');
        $content = $view->toString();
        $this->assertViewContent('TYPE:error,MESSAGE:COMPONENT METHOD CALLED', $content);
    }
    
    /** @test */
    public function class_components_can_define_dependencies()
    {
        Blade::component(Dependency::class, 'with-dependency');
        
        $view = TestApp::view('with-dependency-component')->with('message', 'bar');
        $content = $view->toString();
        $this->assertViewContent('MESSAGE:foobar', $content);
    }
    
    /** @test */
    public function component_attributes_are_passed()
    {
        Blade::component(AlertAttributes::class, 'alert-attributes');
        
        $view = TestApp::view('alert-attributes-component')->with('message', 'foo');
        $content = $view->toString();
        $this->assertViewContent('ID:alert-component,CLASS:mt-4,MESSAGE:foo,TYPE:error', $content);
    }
    
    /** @test */
    public function slots_works()
    {
        Blade::component(ToUppercaseComponent::class, 'uppercase');
        
        $view = TestApp::view('uppercase-component')->with('content', 'foobar');
        $content = $view->toString();
        $this->assertViewContent('TITLE:CALVIN,CONTENT:FOOBAR', $content);
        
        // with scope
        $view = TestApp::view('uppercase-component')->with([
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
        
        $view = TestApp::view('inline-component')->with('content', 'foobar');
        $content = $view->toString();
        $this->assertViewContent('Content:FOOBAR,SLOT:CALVIN', $content);
    }
    
    /** @test */
    public function dynamic_components_work()
    {
        $view = TestApp::view('dynamic-component')->with('componentName', 'hello');
        $content = $view->toString();
        $this->assertViewContent('Hello World', $content);
        
        $view = TestApp::view('dynamic-component')->with('componentName', 'hello-calvin');
        $content = $view->toString();
        $this->assertViewContent('Hello Calvin', $content);
    }
    
}