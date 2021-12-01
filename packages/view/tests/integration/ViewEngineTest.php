<?php

declare(strict_types=1);

namespace Tests\View\integration;

use Snicco\View\ViewEngine;
use Snicco\View\GlobalViewContext;
use Codeception\TestCase\WPTestCase;
use Snicco\View\ViewComposerCollection;
use Snicco\View\Implementations\PHPView;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Implementations\PHPViewFinder;
use Snicco\View\Implementations\PHPViewFactory;
use Snicco\View\Exceptions\ViewNotFoundException;
use Snicco\View\Exceptions\ViewRenderingException;
use Snicco\View\Implementations\NewableInstanceViewComposerFactory;

use const VIEW_TEST_DIR;

class ViewEngineTest extends WPTestCase
{
    
    /**
     * @var ViewEngine
     */
    private $view_engine;
    
    /**
     * @var GlobalViewContext
     */
    private $global_view_context;
    
    /**
     * @var ViewComposerCollection
     */
    private $composers;
    
    /**
     * @var string
     */
    private $view_dir;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->view_dir = SHARED_FIXTURES_DIR.DS.'views';
        
        $this->global_view_context = new GlobalViewContext();
        $this->composers = new ViewComposerCollection(
            new NewableInstanceViewComposerFactory(),
            $this->global_view_context
        );
        $this->view_engine = new ViewEngine(
            new PHPViewFactory(
                new PHPViewFinder([$this->view_dir]),
                $this->composers
            )
        );
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        
        if (is_file($this->view_dir.'/framework/redirect-protection.php')) {
            unlink($this->view_dir.'/framework/redirect-protection.php');
        }
    }
    
    /** @test */
    public function a_basic_view_can_be_created()
    {
        $view = $this->view_engine->make('view.php');
        
        $this->assertSame('Foobar', $view->toString());
    }
    
    /** @test */
    public function the_view_factory_is_set_as_context_on_the_view()
    {
        $view = $this->view_engine->make('view.php');
        $this->assertSame($this->view_engine, $view->context('__view'));
    }
    
    /** @test */
    public function a_nested_view_can_be_rendered_with_dot_notation()
    {
        $view = $this->view_engine->make('subdirectory.nested-view');
        
        $this->assertSame('Nested View', $view->toString());
    }
    
    /** @test */
    public function non_existing_views_throw_an_exception()
    {
        $this->expectExceptionMessage('Non of the provided views exists. Tried: [bogus.php]');
        $this->expectException(ViewNotFoundException::class);
        
        $this->view_engine->make('bogus.php');
    }
    
    /** @test */
    public function multiple_views_can_be_passed_but_only_the_first_one_gets_rendered()
    {
        $view = $this->view_engine->make(['bogus', 'view', 'welcome.wordpress']);
        
        $this->assertSame('Foobar', $view->toString());
    }
    
    /** @test */
    public function a_view_can_be_rendered_rendered()
    {
        $view_content = $this->view_engine->render('view-with-context.php', ['world' => 'World']);
        
        $this->assertSame('Hello World', $view_content);
    }
    
    /** @test */
    public function the_prefix_of_the_global_context_can_be_set_and_accessed_with_dot_notation()
    {
        $this->global_view_context->add('calvin', [
            'foo' => [
                'bar' => [
                    'baz' => 'World',
                ],
            ],
        ]);
        
        $view = $this->view_engine->make('view-with-global-context.php');
        
        $this->assertSame('Hello World', $view->toString());
    }
    
    /** @test */
    public function multiple_global_variables_can_be_shared()
    {
        $this->global_view_context->add('foo', 'bar');
        $this->global_view_context->add('baz', 'biz');
        
        $view = $this->view_engine->make('multiple-globals.php');
        
        $this->assertSame('barbiz', $view->toString());
    }
    
    /** @test */
    public function view_composers_have_precedence_over_globals()
    {
        $this->global_view_context->add('variable', ['foo' => 'bar']);
        $this->composers->addComposer('view-overlapping-context', function (PHPView $view) {
            $view->with([
                'variable' => [
                    
                    'foo' => 'baz',
                
                ],
            ]);
        });
        
        $view = $this->view_engine->make('view-overlapping-context');
        
        $this->assertSame('baz', $view->toString());
    }
    
    /** @test */
    public function local_context_has_precedence_over_composers_and_globals()
    {
        $this->global_view_context->add('variable', [
            'foo' => 'bar',
        ]);
        
        $this->composers->addComposer('view-overlapping-context', function (PHPView $view) {
            $view->with([
                'variable' => [
                    'foo' => 'baz',
                ],
            ]);
        });
        
        $view = $this->view_engine->make('view-overlapping-context')->with([
            'variable' => [
                'foo' => 'biz',
            ],
        ]);
        
        $this->assertSame('biz', $view->toString());
    }
    
    /** @test */
    public function one_view_can_be_rendered_from_within_another()
    {
        $view = $this->view_engine->make('view-includes');
        
        $this->assertSame('Hello World', $view->toString());
    }
    
    /** @test */
    public function views_with_errors_dont_print_output_to_the_client()
    {
        $view = $this->view_engine->make('with-error');
        
        ob_start();
        try {
            $view->toString();
            $this->fail("No error occurred when rendering test view.");
        } catch (ViewRenderingException $e) {
            $this->assertSame(
                "Error rendering view: [with-error].\nCaused by: Call to undefined function non_existing_function()",
                $e->getMessage()
            );
            $this->assertSame('', ob_get_clean());
        }
    }
    
    /** @test */
    public function errors_in_nested_views_dont_print_output_to_the_client()
    {
        $this->expectException(ViewRenderingException::class);
        $this->expectExceptionMessage('Error rendering view: [with-error-subview].');
        
        $view = $this->view_engine->make('with-error-subview');
        ob_start();
        try {
            $view->toString();
        }
        finally {
            $this->assertSame('', ob_get_clean());
        }
    }
    
    /** @test */
    public function views_in_the_application_with_the_same_path_as_the_framework_have_priority_over_framework_views()
    {
        file_put_contents(
            $this->view_dir.'/framework/redirect-protection.php',
            "<?php echo 'Redirecting';"
        );
        
        $content = $this->view_engine->make('framework.redirect-protection')->toString();
        
        $this->assertSame('Redirecting', $content);
    }
    
    /** @test */
    public function views_can_extend_parent_views()
    {
        $view = $this->view_engine->make('subdirectory.subview');
        
        $this->assertSame('Hello World', $view->toString());
    }
    
    /** @test */
    public function child_view_content_is_rendered_into_a_content_variable()
    {
        $view = $this->view_engine->make('subdirectory.child');
        
        $this->assertSame('World Hello', $view->toString());
    }
    
    /** @test */
    public function child_view_content_can_be_extended_multiple_times()
    {
        $view = $this->view_engine->make('subdirectory.nested-child');
        
        $this->assertSame('foo bar Hello', $view->toString());
    }
    
    /** @test */
    public function extended_parents_view_are_also_passed_through_view_composers()
    {
        $this->composers->addComposer('parent-with-view-composer', function (ViewInterface $view) {
            $view->with('composed_value', 'BAR');
        });
        
        $view = $this->view_engine->make('child-with-parent-view-composer');
        $this->assertSame('BAR Child-Content:FOO', trim($view->toString(), "\t\n\r\0\x0B"));
    }
    
    /** @test */
    public function child_view_context_is_shared_with_parent_view()
    {
        $view =
            $this->view_engine->make('child-that-shares-context')->with('shared_variable', 'BAZ');
        $this->assertSame('PARENT CHILD:FOO Shared:BAZ', trim($view->toString(), "\t\n\r\0\x0B"));
    }
    
    /** @test */
    public function the_root_view_can_be_retrieved()
    {
        $view = $this->view_engine->make('subdirectory.subview');
        
        $this->assertSame('Hello World', $view->toString());
        
        $this->assertSame($view, $this->view_engine->rootView());
    }
    
}