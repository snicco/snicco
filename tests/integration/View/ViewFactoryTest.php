<?php

declare(strict_types=1);

namespace Tests\integration\View;

use Tests\TestCase;
use Tests\stubs\TestApp;
use Snicco\View\PhpView;
use Snicco\View\ViewFactory;
use Snicco\ExceptionHandling\Exceptions\ViewException;
use Snicco\ExceptionHandling\Exceptions\ViewNotFoundException;

class ViewFactoryTest extends TestCase
{
    
    private ViewFactory $view_service;
    
    /** @test */
    public function a_basic_view_can_be_created()
    {
        
        $view = $this->view_service->make('view.php');
        
        $this->assertSame('Foobar', $view->toString());
        
    }
    
    /** @test */
    public function non_existing_views_throw_an_exception()
    {
        
        $this->expectExceptionMessage('Views not found. Tried [bogus.php]');
        $this->expectException(ViewNotFoundException::class);
        
        $this->view_service->make('bogus.php');
        
    }
    
    /** @test */
    public function multiple_views_can_be_passed_but_only_the_first_one_gets_rendered()
    {
        
        $view = $this->view_service->make(['bogus', 'view', 'welcome.wordpress']);
        
        $this->assertSame('Foobar', $view->toString());
        
    }
    
    /** @test */
    public function a_view_can_be_rendered_rendered_outside_of_the_routing_flow()
    {
        
        $view_content = $this->view_service->render('view-with-context.php', ['world' => 'World']);
        
        $this->assertSame('Hello World', $view_content);
        
    }
    
    /** @test */
    public function the_prefix_of_the_global_context_can_be_set_and_accessed_with_dot_notation()
    {
        
        TestApp::globals('calvin', [
            'foo' => [
                'bar' => [
                    'baz' => 'World',
                ],
            ],
        ]);
        
        $view = $this->view_service->make('view-with-global-context.php');
        
        $this->assertSame('Hello World', $view->toString());
        
    }
    
    /** @test */
    public function multiple_global_variables_can_be_shared()
    {
        
        TestApp::globals('foo', 'bar');
        TestApp::globals('baz', 'biz');
        
        $view = $this->view_service->make('multiple-globals.php');
        
        $this->assertSame('barbiz', $view->toString());
        
    }
    
    /** @test */
    public function view_composers_have_precedence_over_globals()
    {
        
        TestApp::globals('variable', [
            'foo' => 'bar',
        ]);
        
        TestApp::addComposer('view-overlapping-context', function (PhpView $view) {
            
            $view->with([
                'variable' => [
                    
                    'foo' => 'baz',
                
                ],
            ]);
            
        });
        
        $view = $this->view_service->make('view-overlapping-context');
        
        $this->assertSame('baz', $view->toString());
        
    }
    
    /** @test */
    public function local_context_has_precedence_over_composers_and_globals()
    {
        
        TestApp::globals('variable', [
            'foo' => 'bar',
        ]);
        
        TestApp::addComposer('view-overlapping-context', function (PhpView $view) {
            
            $view->with([
                'variable' => [
                    
                    'foo' => 'baz',
                
                ],
            ]);
            
        });
        
        $view = $this->view_service->make('view-overlapping-context')->with([
            
            'variable' => [
                
                'foo' => 'biz',
            ],
        
        ]);
        
        $this->assertSame('biz', $view->toString());
        
    }
    
    /** @test */
    public function exception_gets_thrown_for_non_existing_views()
    {
        
        $this->expectExceptionMessage('Views not found. Tried [viewss.php]');
        
        $this->view_service->make('viewss.php');
        
    }
    
    /** @test */
    public function one_view_can_be_rendered_from_within_another()
    {
        
        $view = $this->view_service->make('view-includes');
        
        $this->assertSame('Hello World', $view->toString());
        
    }
    
    /** @test */
    public function views_can_be_included_in_parent_views()
    {
        
        $view = $this->view_service->make('subview.php');
        
        $this->assertSame('Hello World', $view->toString());
        
    }
    
    /** @test */
    public function views_with_errors_dont_print_output_to_the_client()
    {
        
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Error rendering view: [with-error].');
        
        $view = $this->view_service->make('with-error');
        
        $view->toString();
        
    }
    
    /** @test */
    public function errors_in_nested_views_dont_print_output_to_the_client()
    {
        
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Error rendering view: [with-error-subview].');
        
        $view = $this->view_service->make('with-error-subview');
        
        $view->toString();
        
    }
    
    protected function setUp() :void
    {
        
        $this->afterApplicationCreated(function () {
            $this->view_service = $this->app->resolve(ViewFactory::class);
        });
        
        parent::setUp();
        
    }
    
}