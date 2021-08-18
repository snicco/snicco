<?php

declare(strict_types=1);

namespace Tests\unit\View;

use Tests\stubs\TestView;
use PHPUnit\Framework\TestCase;
use Snicco\Contracts\ViewComposer;
use Tests\helpers\CreateContainer;
use Snicco\Contracts\ViewInterface;
use Snicco\Contracts\PhpViewInterface;
use Tests\fixtures\TestDependencies\Foo;
use Snicco\Factories\ViewComposerFactory;
use Tests\fixtures\ViewComposers\FooComposer;

use const TEST_CONFIG;

class ViewComposerFactoryTest extends TestCase
{
    
    use CreateContainer;
    
    private ViewComposerFactory $factory;
    
    /** @test */
    public function a_closure_can_be_a_view_composer()
    {
        
        $foo = function (ViewInterface $view, Foo $foo) {
            
            $view->with(['foo' => $foo->foo]);
            
        };
        
        $composer = $this->factory->createUsing($foo);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->executeUsing($view = $this->newPhpView());
        
        $this->assertSame('foo', $view->context('foo'));
        
    }
    
    private function newPhpView() :PhpViewInterface
    {
        
        return new TestView('foo');
        
    }
    
    /** @test */
    public function a_fully_qualified_namespaced_class_can_be_a_composer()
    {
        
        $controller = FooComposer::class.'@compose';
        
        $composer = $this->factory->createUsing($controller);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->executeUsing($view = $this->newPhpView());
        
        $this->assertSame('foobar', $view->context('foo'));
        
    }
    
    /** @test */
    public function an_array_callable_can_be_a_composer()
    {
        
        $controller = [FooComposer::class, 'compose'];
        
        $composer = $this->factory->createUsing($controller);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->executeUsing($view = $this->newPhpView());
        
        $this->assertEquals('foobar', $view->context('foo'));
        
    }
    
    /** @test */
    public function non_existing_composer_classes_raise_an_exception()
    {
        
        $this->expectExceptionMessage("[FooController, 'handle'] is not a valid callable.");
        
        $controller = 'FooController@handle';
        
        $this->factory->createUsing($controller);
        
    }
    
    /** @test */
    public function non_callable_methods_on_a_composer_raise_an_exception()
    {
        
        $this->expectExceptionMessage(
            "[".FooComposer::class.", 'invalidMethod'] is not a valid callable."
        );
        
        $controller = [FooComposer::class, 'invalidMethod'];
        
        $this->factory->createUsing($controller);
        
    }
    
    /** @test */
    public function passing_an_array_with_the_method_prefixed_with_an_at_sign_also_works()
    {
        
        $controller = [FooComposer::class, '@compose'];
        
        $composer = $this->factory->createUsing($controller);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->executeUsing($view = $this->newPhpView());
        
        $this->assertEquals('foobar', $view->context('foo'));
        
    }
    
    /** @test */
    public function composers_can_be_resolved_without_the_fqn()
    {
        
        $controller = ['FooComposer', 'compose'];
        
        $composer = $this->factory->createUsing($controller);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->executeUsing($view = $this->newPhpView());
        
        $this->assertEquals('foobar', $view->context('foo'));
        
    }
    
    /** @test */
    public function if_no_method_is_specified_compose_is_assumed()
    {
        
        $controller = FooComposer::class;
        
        $composer = $this->factory->createUsing($controller);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->executeUsing($view = $this->newPhpView());
        
        $this->assertSame('foobar', $view->context('foo'));
        
    }
    
    protected function setUp() :void
    {
        
        parent::setUp();
        
        $this->factory =
            new ViewComposerFactory(TEST_CONFIG['composers'], $this->createContainer());
        
    }
    
}
