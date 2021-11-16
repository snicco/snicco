<?php

declare(strict_types=1);

namespace Tests\unit\Factories;

use Tests\UnitTest;
use Tests\stubs\TestView;
use Snicco\Contracts\ViewComposer;
use Tests\concerns\CreateContainer;
use Snicco\Contracts\ViewInterface;
use Snicco\Contracts\PhpViewInterface;
use Snicco\Factories\ViewComposerFactory;
use Tests\fixtures\ViewComposers\FooComposer;

use const TEST_CONFIG;

class ViewComposerFactoryTest extends UnitTest
{
    
    use CreateContainer;
    
    private ViewComposerFactory $factory;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->factory =
            new ViewComposerFactory($this->createContainer(), TEST_CONFIG['composers']);
    }
    
    /** @test */
    public function a_closure_can_be_a_view_composer()
    {
        $foo = function (ViewInterface $view) {
            $view->with(['foo' => 'bar']);
        };
        
        $composer = $this->factory->create($foo);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->compose($view = $this->newPhpView());
        
        $this->assertSame('bar', $view->context('foo'));
    }
    
    /** @test */
    public function a_fully_qualified_namespaced_class_can_be_a_composer()
    {
        $controller = FooComposer::class;
        
        $composer = $this->factory->create($controller);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->compose($view = $this->newPhpView());
        
        $this->assertSame('bar', $view->context('foo'));
    }
    
    /** @test */
    public function non_existing_composer_classes_raise_an_exception()
    {
        $this->expectExceptionMessage("Composer [FooController] could not be created.");
        
        $controller = 'FooController';
        
        $this->factory->create($controller);
    }
    
    /** @test */
    public function composers_can_be_resolved_without_the_fqn()
    {
        $controller = 'FooComposer';
        
        $composer = $this->factory->create($controller);
        
        $this->assertInstanceOf(ViewComposer::class, $composer);
        
        $composer->compose($view = $this->newPhpView());
        
        $this->assertEquals('bar', $view->context('foo'));
    }
    
    private function newPhpView() :ViewInterface
    {
        return new TestView('foo');
    }
    
}
