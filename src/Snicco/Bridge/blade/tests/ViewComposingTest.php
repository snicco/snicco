<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Snicco\Component\Templating\ValueObject\View;

use function trim;

/**
 * @internal
 */
final class ViewComposingTest extends BladeTestCase
{
    /**
     * @test
     */
    public function global_data_can_be_shared_with_all_views(): void
    {
        $this->global_view_context->add('globals', [
            'foo' => 'calvin',
        ]);

        $this->assertSame('calvin', $this->renderView('globals'));
    }

    /**
     * @test
     */
    public function data_is_shared_with_nested_views(): void
    {
        $this->global_view_context->add('globals', [
            'surname' => 'alkan',
        ]);
        $this->composers->addComposer(
            'components.view-composer-parent',
            fn (View $view): View => $view->with([
                'name' => 'calvin',
            ])
        );

        $this->assertSame('calvinalkan', trim($this->renderView('nested-view-composer')));
    }

    /**
     * @test
     */
    public function a_view_composer_can_be_added_to_a_view(): void
    {
        $this->composers->addComposer('view-composer', fn (View $view): View => $view->with([
            'name' => 'calvin',
        ]));

        $this->assertViewContent('calvin', $this->renderView('view-composer'));
    }

    private function renderView(string $view): string
    {
        $view = $this->view_engine->make($view);

        return $this->view_engine->renderView($view);
    }
}
