<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use Snicco\Component\Templating\ViewComposer\ViewComposerFactory;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\Tests\fixtures\TestDoubles\TestView;
use Snicco\Component\Templating\ViewComposer\NewableInstanceViewComposerFactory;

class ViewComposerCollectionTest extends TestCase
{
    
    private ViewComposerFactory $factory;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->factory = new NewableInstanceViewComposerFactory();
    }
    
    /** @test */
    public function a_view_can_be_composed_if_it_has_a_matching_composer()
    {
        $collection = $this->newViewComposerCollection();
        
        $view = new TestView('foo_view');
        
        $collection->addComposer('foo_view', function (View $view) {
            $view->with(['foo' => 'baz']);
        });
        
        $collection->compose($view);
        
        $this->assertSame(['foo' => 'baz'], $view->context());
    }
    
    /** @test */
    public function the_view_is_not_changed_if_no_composer_matches()
    {
        $collection = $this->newViewComposerCollection();
        
        $view = new TestView('foo_view');
        
        $collection->addComposer('bar_view', function (View $view) {
            $view->with(['foo' => 'baz']);
        });
        
        $collection->compose($view);
        
        $this->assertSame([], $view->context());
    }
    
    /** @test */
    public function multiple_composers_can_match_for_one_view()
    {
        $collection = $this->newViewComposerCollection();
        
        $view = new TestView('foo_view');
        
        $collection->addComposer('foo_view', function (View $view) {
            $view->with(['foo' => 'bar']);
        });
        
        $collection->addComposer('foo_view', function (View $view) {
            $view->with(['bar' => 'baz']);
        });
        
        $collection->compose($view);
        
        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $view->context());
    }
    
    /** @test */
    public function a_composer_can_be_created_for_multiple_views()
    {
        $collection = $this->newViewComposerCollection();
        
        $collection->addComposer(['view_one', 'view_two'], function (View $view) {
            $view->with(['foo' => 'bar']);
        });
        
        $view1 = new TestView('view_one');
        
        $collection->compose($view1);
        $this->assertSame(['foo' => 'bar'], $view1->context());
        
        $view2 = new TestView('view_two');
        $collection->compose($view2);
        $this->assertSame(['foo' => 'bar'], $view2->context());
    }
    
    /** @test */
    public function the_view_does_not_need_to_be_type_hinted()
    {
        $collection = $this->newViewComposerCollection();
        
        $view = new TestView('foo_view');
        
        $collection->addComposer('foo_view', function ($view_file) {
            $view_file->with(['foo' => 'baz']);
        });
        
        $collection->compose($view);
        
        $this->assertSame(['foo' => 'baz'], $view->context());
    }
    
    /** @test */
    public function test_exception_for_adding_a_bad_view_composer()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $collection = $this->newViewComposerCollection();
        
        $collection->addComposer('foo_view', 1);
    }
    
    /** @test */
    public function a_view_composer_can_be_a_class()
    {
        $collection = $this->newViewComposerCollection();
        
        $view = new TestView('foo_view');
        
        $collection->addComposer('foo_view', TestComposer::class);
        
        $collection->compose($view);
        
        $this->assertSame(['foo' => 'baz'], $view->context());
    }
    
    /** @test */
    public function test_exception_for_bad_composer_class()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("[BadComposer] is not a valid class.");
        
        $collection = $this->newViewComposerCollection();
        
        $collection->addComposer('foo_view', 'BadComposer');
    }
    
    /** @test */
    public function test_exception_for_composer_without_interface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Class [%s] does not implement [%s]",
                ComposerWithoutInterface::class,
                ViewComposer::class
            )
        );
        
        $collection = $this->newViewComposerCollection();
        
        $collection->addComposer('foo_view', ComposerWithoutInterface::class);
    }
    
    /** @test */
    public function local_context_has_priority_over_a_view_composer()
    {
        $collection = $this->newViewComposerCollection();
        
        $view = new TestView('foo_view');
        $view->with('foo', 'bar');
        
        $collection->addComposer('foo_view', function (View $view) {
            $view->with(['foo' => 'baz']);
        });
        
        $collection->compose($view);
        
        $this->assertSame(['foo' => 'bar'], $view->context());
    }
    
    /** @test */
    public function view_composer_have_priority_over_global_context()
    {
        $collection = $this->newViewComposerCollection($global_context = new GlobalViewContext());
        $global_context->add('foo', 'bar');
        
        $view = new TestView('foo_view');
        
        $collection->addComposer('foo_view', function (View $view) {
            $view->with(['foo' => 'baz']);
        });
        
        $collection->compose($view);
        
        $this->assertSame(['foo' => 'baz'], $view->context());
    }
    
    /** @test */
    public function view_composers_can_match_by_wild_card()
    {
        $collection = $this->newViewComposerCollection();
        
        $collection->addComposer('foo*', function (View $view) {
            $view->with(['foo' => 'bar']);
        });
        
        $view = new TestView('foo');
        $collection->compose($view);
        $this->assertSame(['foo' => 'bar'], $view->context());
        
        $view = new TestView('foobar');
        $collection->compose($view);
        $this->assertSame(['foo' => 'bar'], $view->context());
        
        $view = new TestView('foobiz');
        $collection->compose($view);
        $this->assertSame(['foo' => 'bar'], $view->context());
        
        $view = new TestView('bar');
        $collection->compose($view);
        $this->assertSame([], $view->context());
    }
    
    private function newViewComposerCollection(GlobalViewContext $global_view_context = null) :ViewComposerCollection
    {
        return new ViewComposerCollection($this->factory, $global_view_context);
    }
    
}

class TestComposer implements ViewComposer
{
    
    public function compose(View $view) :void
    {
        $view->with(['foo' => 'baz']);
    }
    
}

class ComposerWithoutInterface
{
    
    public function compose(View $view) :void
    {
        $view->with(['foo' => 'baz']);
    }
    
}
