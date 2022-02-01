<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\ParameterBag\ParameterPag;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\Tests\fixtures\TestDoubles\TestView;
use Snicco\Component\Templating\View\PHPView;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\NewableInstanceViewComposerFactory;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewEngine;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;
use Snicco\Component\Templating\ViewFactory\PHPViewFinder;
use Snicco\Component\Templating\ViewFactory\ViewFactory;

use function get_class;
use function ob_get_clean;
use function ob_start;
use function realpath;

use const DIRECTORY_SEPARATOR;

class ViewEngineTest extends TestCase
{

    private ViewEngine $view_engine;
    private GlobalViewContext $global_view_context;
    private ViewComposerCollection $composers;

    private string $view_dir;

    /** @test */
    public function a_view_can_be_created()
    {
        $view = $this->view_engine->make('foo.php');
        $this->assertSame('foo', $view->toString());

        $view = $this->view_engine->make('foo');
        $this->assertSame('foo', $view->toString());
    }

    /** @test */
    public function a_view_can_be_created_from_an_absolute_path()
    {
        $path = realpath($this->view_dir . '/foo.php');

        $view = $this->view_engine->make($path);
        $this->assertInstanceOf(PHPView::class, $view);
        $this->assertSame($path, $view->path());
        $this->assertSame('foo', $view->toString());
    }

    /** @test */
    public function a_view_as_access_to_the_view_engine()
    {
        $view = $this->view_engine->make('has-engine.php');
        $this->assertSame('View has engine: ' . get_class($this->view_engine), $view->toString());
    }

    /** @test */
    public function a_nested_view_can_be_rendered_with_dot_notation()
    {
        $view = $this->view_engine->make('components.input');
        $this->assertEquals('input-component', $view->toString());

        $view = $this->view_engine->make('components.input.php');
        $this->assertEquals('input-component', $view->toString());
    }

    /** @test */
    public function non_existing_views_throw_an_exception()
    {
        $this->expectExceptionMessage(
            'None of the used view factories can render the view [bogus.php].'
        );
        $this->expectException(ViewNotFound::class);

        $this->view_engine->make('bogus.php');
    }

    /** @test */
    public function a_view_can_be_rendered_to_a_string()
    {
        $view_content = $this->view_engine->render('greeting', ['name' => 'Calvin']);

        $this->assertSame('Hello Calvin', $view_content);
    }

    /** @test */
    public function the_prefix_of_the_global_context_can_be_set_and_accessed_with_dot_notation()
    {
        $this->global_view_context->add('app_context', [
            'foo' => [
                'bar' => 'baz',
            ],
        ]);

        $view = $this->view_engine->make('global-context.php');

        $this->assertSame('baz', $view->toString());
    }

    /** @test */
    public function multiple_global_variables_can_be_shared()
    {
        $this->global_view_context->add('global1', ['foo' => ['bar' => 'baz']]);
        $this->global_view_context->add('global2', ['foo' => ['bar' => 'biz']]);

        $view = $this->view_engine->make('multiple-globals');

        $this->assertSame('baz:biz', $view->toString());
    }

    /** @test */
    public function view_composers_have_precedence_over_globals()
    {
        $this->global_view_context->add('test_context', ['foo' => ['bar' => 'baz']]);

        $this->composers->addComposer('context-priority', function (PHPView $view) {
            $view->with([
                'test_context' => new ParameterPag([
                    'foo' => ['bar' => 'biz'],
                ]),
            ]);
        });

        $view = $this->view_engine->make('context-priority');

        $this->assertSame('biz', $view->toString());
    }

    /** @test */
    public function local_context_has_precedence_over_composers_and_globals()
    {
        $this->global_view_context->add('test_context', ['foo' => ['bar' => 'baz']]);

        $this->composers->addComposer('context-priority', function (PHPView $view) {
            $view->with([
                'test_context' => new ParameterPag([
                    'foo' => ['bar' => 'biz'],
                ]),
            ]);
        });

        $view = $this->view_engine->make('context-priority')
            ->with([
                'test_context' => new ParameterPag([
                    'foo' => ['bar' => 'boom'],
                ]),
            ]);

        $this->assertSame('boom', $view->toString());
    }

    /** @test */
    public function one_view_can_be_rendered_from_within_another()
    {
        $view = $this->view_engine->make('inline-render');

        $this->assertSame('foo:inline=>Hello Calvin', $view->toString());
    }

    /** @test */
    public function views_can_extend_parent_views()
    {
        $view = $this->view_engine->make('partials.post-title')->with('post_title', 'Foobar');
        $this->assertSame('You are viewing post: Foobar', $view->toString());
    }

    /** @test */
    public function views_can_be_extended_multiple_times()
    {
        $view = $this->view_engine->make('partials.post-body')->with('post_body', 'Foo');

        $this->assertSame('You are viewing post: Special Layout: Foo', $view->toString());
    }

    /** @test */
    public function views_with_errors_dont_print_output_to_the_client()
    {
        $view = $this->view_engine->make('bad-function');

        ob_start();
        try {
            $view->toString();
            $this->fail('The view should not be able to render.');
        } catch (ViewCantBeRendered $e) {
            $this->assertSame(
                "Error rendering view [bad-function].\nCaused by: Call to undefined function foo()",
                $e->getMessage()
            );
            $this->assertSame('', ob_get_clean());
        }
    }

    /** @test */
    public function directories_are_searched_by_order_allowing_overwritten_of_view_names()
    {
        $engine = new ViewEngine(
            new PHPViewFactory(
                new PHPViewFinder(
                    [
                        __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'view2',
                        $this->view_dir,
                    ]
                ),
                $this->composers
            )
        );

        $view = $engine->make('foo');
        $this->assertSame('foo modified', $view->toString());
    }

    /** @test */
    public function extended_parents_view_are_also_passed_through_view_composers()
    {
        $this->composers->addComposer(
            'post-layout',
            fn(View $view) => $view->with('sidebar', 'hi')
        );

        $view = $this->view_engine->make('partials.post-title')->with('post_title', 'Foobar');
        $this->assertSame('You are viewing post: Foobar Our Sidebar: hi', $view->toString());
    }

    /** @test */
    public function errors_in_child_views_dont_print_output()
    {
        $this->expectException(ViewCantBeRendered::class);
        $this->expectExceptionMessage('Error rendering view [partials.with-error].');

        $view = $this->view_engine->make('partials.with-error');
        ob_start();
        try {
            $view->toString();
        } finally {
            $this->assertSame('', ob_get_clean());
        }
    }

    /** @test */
    public function child_view_context_is_shared_with_parent_view()
    {
        $view = $this->view_engine->make('partials.share-var')->with('child_var', 'BAZ');
        $this->assertSame('Var from child template: BAZ', $view->toString());
    }

    /** @test */
    public function multiple_view_factories_can_be_used_together()
    {
        $view_engine = new ViewEngine($this->php_view_factory, new TestTwigViewFactory());

        $php_view = $view_engine->make('foo.php');
        $this->assertInstanceOf(PHPView::class, $php_view);

        $twig_view = $view_engine->make('test.twig');
        $this->assertInstanceOf(TestView::class, $twig_view);
    }

    /** @test */
    public function test_exception_when_no_view_factory_can_render_a_view()
    {
        $view_engine = new ViewEngine($this->php_view_factory, new TestTwigViewFactory());

        try {
            $view_engine->make('foo.xml');
        } catch (ViewNotFound $e) {
            $this->assertStringStartsWith(
                'None of the used view factories can render the view [foo.xml].',
                $e->getMessage()
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->view_dir = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'views';

        $this->global_view_context = new GlobalViewContext();
        $this->composers = new ViewComposerCollection(
            new NewableInstanceViewComposerFactory(),
            $this->global_view_context
        );

        $this->php_view_factory = new PHPViewFactory(
            new PHPViewFinder([$this->view_dir]),
            $this->composers
        );

        $this->view_engine = new ViewEngine($this->php_view_factory);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_file($this->view_dir . '/framework/redirect-protection.php')) {
            unlink($this->view_dir . '/framework/redirect-protection.php');
        }
    }

}

class TestTwigViewFactory implements ViewFactory
{

    public function make(string $view): View
    {
        if (!strpos($view, 'twig')) {
            throw new ViewNotFound();
        }
        return new TestView($view);
    }

}