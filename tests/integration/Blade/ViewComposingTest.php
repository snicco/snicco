<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Snicco\Blade\BladeView;
use Snicco\Blade\BladeViewFactory;
use Snicco\View\GlobalViewContext;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;

class ViewComposingTest extends BladeTestCase
{
    
    private BladeViewFactory $engine;
    
    private GlobalViewContext $global_view_context;
    
    private ViewComposerCollection $composers;
    
    protected function setUp() :void
    {
        $this->afterApplicationBooted(function () {
            $this->engine = $this->app[ViewFactory::class];
            $this->global_view_context = $this->app[GlobalViewContext::class];
            $this->composers = $this->app[ViewComposerCollection::class];
        });
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function global_data_can_be_shared_with_all_views()
    {
        $this->global_view_context->add('globals', ['foo' => 'calvin']);
        
        $this->assertSame('calvin', $this->makeView('globals'));
    }
    
    /** @test */
    public function data_is_shared_with_nested_views()
    {
        $this->global_view_context->add('globals', ['surname' => 'alkan']);
        $this->composers->addComposer(
            'components.view-composer-parent',
            function (BladeView $view) {
                $view->with(['name' => 'calvin']);
            }
        );
        
        $this->assertSame('calvinalkan', $this->makeView('nested-view-composer'));
    }
    
    /** @test */
    public function a_view_composer_can_be_added_to_a_view()
    {
        $this->composers->addComposer('view-composer', function (BladeView $view) {
            $view->with(['name' => 'calvin']);
        });
        
        $this->assertViewContent('calvin', $this->makeView('view-composer'));
    }
    
    /** @test */
    public function blade_views_have_access_to_the_framework_globals()
    {
        $this->assertViewContent('view has session', $this->makeView('session'));
    }
    
    private function makeView(string $view)
    {
        $view = $this->engine->make($view);
        return $view->toString();
    }
    
}