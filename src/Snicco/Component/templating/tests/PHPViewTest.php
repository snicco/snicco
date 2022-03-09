<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;
use Snicco\Component\Templating\ViewFactory\PHPViewFinder;

final class PHPViewTest extends TestCase
{
    private PHPViewFactory $php_view_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->php_view_factory = new PHPViewFactory(
            new PHPViewFinder([__DIR__ . '/fixtures/views']),
            new ViewComposerCollection(null, new GlobalViewContext())
        );
    }

    /**
     * @test
     */
    public function test_context(): void
    {
        $view = $this->php_view_factory->make('foo');
        $this->assertSame([], $view->context());

        $view1 = $view->with('foo', 'bar');
        $this->assertSame([
            'foo' => 'bar',
        ], $view1->context());

        $this->assertSame([], $view->context());

        $view = $view->with('foo', 'baz');
        $this->assertSame([
            'foo' => 'baz',
        ], $view->context());

        $view = $view->with('bar', 'biz');
        $this->assertSame([
            'foo' => 'baz',
            'bar' => 'biz',
        ], $view->context());
    }
}
