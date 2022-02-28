<?php

declare(strict_types=1);


namespace Snicco\Component\Templating\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewEngine;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;
use Snicco\Component\Templating\ViewFactory\PHPViewFinder;

final class PHPViewTest extends TestCase
{

    private PHPViewFactory $php_view_factory;
    private ViewEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->php_view_factory = new PHPViewFactory(
            new PHPViewFinder([__DIR__ . '/fixtures/views']),
            new ViewComposerCollection(null, $context = new GlobalViewContext())
        );
        $this->engine = new ViewEngine($this->php_view_factory);
        $context->add('view', $this->engine);
    }

    /**
     * @test
     */
    public function test_context(): void
    {
        $view = $this->php_view_factory->make('foo');
        $this->assertSame([], $view->context());

        $view->addContext('foo', 'bar');
        $this->assertSame(['foo' => 'bar'], $view->context());

        $view->addContext('foo', 'baz');
        $this->assertSame(['foo' => 'baz'], $view->context());

        $view->addContext('bar', 'biz');
        $this->assertSame(['foo' => 'baz', 'bar' => 'biz'], $view->context());

        $view->withContext(['boom' => 'bam']);
        $this->assertSame(['boom' => 'bam'], $view->context());
    }

    /**
     * @test
     */
    public function the_view_engine_is_always_accessible_even_if_context_is_reset(): void
    {
        $view = $this->engine->make('inline-render');
        $this->assertSame('foo:inline=>Hello Calvin', $view->render());

        $view->addContext('name', 'Marlon');
        $this->assertSame('foo:inline=>Hello Marlon', $view->render());

        $view->withContext(['name' => 'Calvin']);
        $this->assertSame('foo:inline=>Hello Calvin', $view->render());
    }


}