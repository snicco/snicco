<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\StrArr\Str;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\ValueObject\FilePath;
use Snicco\Component\Templating\ValueObject\View;
use Snicco\Component\Templating\ViewComposer\NewableInstanceViewComposerFactory;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewComposer\ViewComposerFactory;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;

use function str_replace;

/**
 * @internal
 */
final class ViewComposerCollectionTest extends TestCase
{
    private ViewComposerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new NewableInstanceViewComposerFactory();
    }

    /**
     * @test
     */
    public function a_view_can_be_composed_if_it_has_a_matching_composer(): void
    {
        $collection = $this->newViewComposerCollection();

        $view = $this->aValidPHPView('foo');

        $collection->addComposer('foo', fn(View $view): View => $view->with([
            'foo' => 'baz',
        ]));

        $view = $collection->compose($view);

        $this->assertSame([
            'foo' => 'baz',
        ], $view->context());
    }

    /**
     * @test
     */
    public function the_view_is_not_changed_if_no_composer_matches(): void
    {
        $collection = $this->newViewComposerCollection();

        $view = $this->aValidPHPView('foo');

        $collection->addComposer('bar', fn(View $view): View => $view->with([
            'foo' => 'baz',
        ]));

        $view = $collection->compose($view);

        $this->assertSame([], $view->context());
    }

    /**
     * @test
     */
    public function multiple_composers_can_match_for_one_view(): void
    {
        $collection = $this->newViewComposerCollection();

        $view = $this->aValidPHPView('foo');

        $collection->addComposer('foo', fn(View $view): View => $view->with([
            'foo' => 'bar',
        ]));
        $collection->addComposer('foo', fn(View $view): View => $view->with([
            'bar' => 'baz',
        ]));

        $view = $collection->compose($view);

        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $view->context());
    }

    /**
     * @test
     */
    public function a_composer_can_be_created_for_multiple_views(): void
    {
        $collection = $this->newViewComposerCollection();

        $collection->addComposer(['foo', 'bar'], fn(View $view): View => $view->with([
            'foo' => 'bar',
        ]));

        $view1 = $this->aValidPHPView('foo');

        $view1 = $collection->compose($view1);
        $this->assertSame([
            'foo' => 'bar',
        ], $view1->context());

        $view2 = $this->aValidPHPView('bar');
        $view2 = $collection->compose($view2);
        $this->assertSame([
            'foo' => 'bar',
        ], $view2->context());
    }

    /**
     * @test
     */
    public function a_view_composer_can_be_a_class(): void
    {
        $collection = $this->newViewComposerCollection();

        $view = $this->aValidPHPView('foo');

        $collection->addComposer('foo', TestComposer::class);

        $view = $collection->compose($view);

        $this->assertSame([
            'foo' => 'baz',
        ], $view->context());
    }

    /**
     * @test
     */
    public function local_context_has_priority_over_a_view_composer(): void
    {
        $collection = $this->newViewComposerCollection();

        $view = $this->aValidPHPView('foo');
        $view = $view->with('foo', 'bar');

        $collection->addComposer('foo', fn(View $view): View => $view->with([
            'foo' => 'baz',
        ]));

        $view = $collection->compose($view);

        $this->assertSame([
            'foo' => 'bar',
        ], $view->context());
    }

    /**
     * @test
     */
    public function view_composer_have_priority_over_global_context(): void
    {
        $collection = $this->newViewComposerCollection($global_context = new GlobalViewContext());
        $global_context->add('foo', 'bar');

        $view = $this->aValidPHPView('foo');

        $collection->addComposer('foo', fn(View $view): View => $view->with([
            'foo' => 'baz',
        ]));

        $view = $collection->compose($view);

        $this->assertSame([
            'foo' => 'baz',
        ], $view->context());
    }

    /**
     * @test
     */
    public function view_composers_can_match_by_wild_card(): void
    {
        $collection = $this->newViewComposerCollection();

        $collection->addComposer('partials.post*', fn(View $view): View => $view->with([
            'foo' => 'bar',
        ]));

        $view = $this->aValidPHPView('partials.post-body');
        $view = $collection->compose($view);
        $this->assertSame([
            'foo' => 'bar',
        ], $view->context());

        $view = $this->aValidPHPView('partials.post-title');
        $view = $collection->compose($view);
        $this->assertSame([
            'foo' => 'bar',
        ], $view->context());

        $view = $this->aValidPHPView('partials.share-var');
        $view = $collection->compose($view);
        $this->assertSame([
        ], $view->context());

        $view = $this->aValidPHPView('foo');
        $view = $collection->compose($view);
        $this->assertSame([
        ], $view->context());
    }

    /**
     * @test
     */
    public function global_context_can_be_a_closure(): void
    {
        $collection = $this->newViewComposerCollection($global_context = new GlobalViewContext());
        $global_context->add('foo', fn(): string => 'bar');

        $view = $this->aValidPHPView('foo');

        $view = $collection->compose($view);

        $this->assertSame([
            'foo' => 'bar',
        ], $view->context());
    }

    private function aValidPHPView(string $view_name): View
    {
        $context = [];
        $name = Str::beforeFirst($view_name, '.php');
        $name = str_replace('.', '/', $name);

        $file = __DIR__ . '/fixtures/views/' . $name . '.php';
        return new View($view_name, FilePath::fromString($file), PHPViewFactory::class, $context);
    }

    private function newViewComposerCollection(GlobalViewContext $global_view_context = null): ViewComposerCollection
    {
        return new ViewComposerCollection($this->factory, $global_view_context);
    }
}

final class TestComposer implements ViewComposer
{
    public function compose(View $view): View
    {
        return $view->with([
            'foo' => 'baz',
        ]);
    }
}

final class ComposerWithoutInterface
{
    public function compose(View $view): View
    {
        return $view->with([
            'foo' => 'baz',
        ]);
    }
}
