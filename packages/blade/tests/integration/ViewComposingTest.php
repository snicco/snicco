<?php

declare(strict_types=1);

namespace Tests\Blade\integration;

use Snicco\Blade\BladeView;
use Tests\Blade\BladeTestCase;

class ViewComposingTest extends BladeTestCase
{
    
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
    
    private function makeView(string $view) :string
    {
        $view = $this->view_engine->make($view);
        return $view->toString();
    }
    
}