<?php

declare(strict_types=1);

namespace Tests\View\unit;

use Tests\Codeception\shared\UnitTest;
use Snicco\View\Contracts\ViewComposer;
use Snicco\View\Contracts\ViewInterface;
use Tests\Core\fixtures\TestDoubles\TestView;
use Tests\View\fixtures\ViewComposers\FooComposer;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\ViewBundle\DependencyInjectionViewComposerFactory;

class ViewComposerFactoryTest extends UnitTest
{
    
    use CreateContainer;
    
    private DependencyInjectionViewComposerFactory $factory;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->factory =
            new DependencyInjectionViewComposerFactory(
                $this->createContainer(),
                ['Tests\View\fixtures\ViewComposers']
            );
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
