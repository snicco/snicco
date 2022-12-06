<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Tests;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Templating\Context\GlobalViewContext;
use Snicco\Component\Templating\Context\NewableInstanceViewComposerFactory;
use Snicco\Component\Templating\Context\ViewContextResolver;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\TemplateEngine;
use Snicco\Component\Templating\ValueObject\FilePath;
use Snicco\Component\Templating\ValueObject\View;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;
use Snicco\Component\Templating\ViewFactory\ViewFactory;

use function file_get_contents;
use function ob_get_clean;
use function ob_start;
use function realpath;

use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
final class TemplateEngineTest extends TestCase
{
    private TemplateEngine $template_engine;

    private GlobalViewContext $global_view_context;

    private ViewContextResolver $composers;

    private PHPViewFactory $php_view_factory;

    private string $view_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->view_dir = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'views';

        $this->global_view_context = new GlobalViewContext();
        $this->composers = new ViewContextResolver(
            $this->global_view_context,
            new NewableInstanceViewComposerFactory()
        );

        $factory = new PHPViewFactory($this->composers, [$this->view_dir]);

        $this->php_view_factory = $factory;

        $this->template_engine = new TemplateEngine($factory);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_file($this->view_dir . '/framework/redirect-protection.php')) {
            unlink($this->view_dir . '/framework/redirect-protection.php');
        }
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_no_view_factory_is_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TemplateEngine();
    }

    /**
     * @test
     */
    public function a_view_can_be_created_and_rendered(): void
    {
        $view = $this->template_engine->make('foo');
        $this->assertSame('foo', $this->template_engine->renderView($view));

        $view = $this->template_engine->make('foo.php');
        $this->assertSame('foo', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function a_view_can_be_created_from_an_absolute_path(): void
    {
        $path = realpath($this->view_dir . '/foo.php');

        if (false === $path) {
            throw new RuntimeException(sprintf('test view [%s] does not exist.', $this->view_dir . '/foo.php'));
        }

        $view = $this->template_engine->make($path);
        $this->assertSame($path, (string) $view->path());
        $this->assertSame('foo', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function a_nested_view_can_be_rendered_with_dot_notation(): void
    {
        $view = $this->template_engine->make('components.input');
        $this->assertSame('input-component', $this->template_engine->renderView($view));

        $view = $this->template_engine->make('components.input.php');
        $this->assertSame('input-component', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function non_existing_views_throw_an_exception(): void
    {
        $this->expectExceptionMessage(
            'None of the used view factories can render the any of the views [bogus.php]'
        );
        $this->expectException(ViewNotFound::class);

        $this->template_engine->make('bogus.php');
    }

    /**
     * @test
     */
    public function a_view_can_be_rendered_to_a_string_directly(): void
    {
        $view_content = $this->template_engine->render('greeting', [
            'name' => 'Calvin',
        ]);

        $this->assertSame('Hello Calvin', $view_content);
    }

    /**
     * @test
     */
    public function the_prefix_of_the_global_context_can_be_set_and_accessed_with_dot_notation(): void
    {
        $this->global_view_context->add('app_context', [
            'foo' => [
                'bar' => 'baz',
            ],
        ]);

        $view = $this->template_engine->make('global-context.php');

        $this->assertSame('baz', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function multiple_global_variables_can_be_shared(): void
    {
        $this->global_view_context->add('global1', [
            'foo' => [
                'bar' => 'baz',
            ],
        ]);
        $this->global_view_context->add('global2', [
            'foo' => [
                'bar' => 'biz',
            ],
        ]);

        $view = $this->template_engine->make('multiple-globals');

        $this->assertSame('baz:biz', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function test_global_view_context_array_access(): void
    {
        $this->global_view_context->add('global1', [
            'foo' => [
                'bar' => 'baz',
            ],
        ]);
        $view = $this->template_engine->make('array-access-isset');

        $this->assertSame('Isset works', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function test_global_view_context_array_access_set_throws_exception(): void
    {
        $this->expectException(ViewCantBeRendered::class);
        $this->expectExceptionMessage('offsetSet');

        $this->global_view_context->add('global1', [
            'foo' => [
                'bar' => 'baz',
            ],
        ]);
        $view = $this->template_engine->make('array-access-set');

        $this->template_engine->renderView($view);
    }

    /**
     * @test
     */
    public function test_global_view_context_array_access_unset_throws_exception(): void
    {
        $this->expectException(ViewCantBeRendered::class);
        $this->expectExceptionMessage('offsetUnset');

        $this->global_view_context->add('global1', [
            'foo' => [
                'bar' => 'baz',
            ],
        ]);
        $view = $this->template_engine->make('array-access-unset');

        $this->template_engine->renderView($view);
    }

    /**
     * @test
     */
    public function view_composers_have_precedence_over_globals(): void
    {
        $this->global_view_context->add('test_context', [
            'foo' => [
                'bar' => 'baz',
            ],
        ]);

        $this->composers->addComposer('context-priority', fn (View $view): View => $view->with([
            'test_context' => [
                'foo' => [
                    'bar' => 'biz',
                ],
            ],
        ]));

        $view = $this->template_engine->make('context-priority');

        $this->assertSame('biz', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function local_context_has_precedence_over_composers_and_globals(): void
    {
        $this->global_view_context->add('test_context', [
            'foo' => [
                'bar' => 'baz',
            ],
        ]);

        $this->composers->addComposer('context-priority', fn (View $view): View => $view->with([
            'test_context' => [
                'foo' => [
                    'bar' => 'biz',
                ],
            ],
        ]));

        $view = $this->template_engine->make('context-priority');
        $view = $view->with([
            'test_context' => [
                'foo' => [
                    'bar' => 'boom',
                ],
            ],
        ]);

        $this->assertSame('boom', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function one_view_can_be_rendered_from_within_another(): void
    {
        $this->global_view_context->add('view', $this->template_engine);
        $view = $this->template_engine->make('inline-render');

        $this->assertSame('foo:inline=>Hello Calvin', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function views_can_extend_parent_views(): void
    {
        $view = $this->template_engine->make('partials.post-title');
        $view = $view->with('post_title', 'Foobar');
        $this->assertSame('You are viewing post: Foobar', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function views_can_extend_parent_views_with_docblock_annotation(): void
    {
        $view = $this->template_engine->make('partials.post-title-docblock');
        $view = $view->with('post_title', 'Foobar');
        $this->assertSame('You are viewing post: Foobar', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function views_can_be_extended_multiple_times(): void
    {
        $view = $this->template_engine->make('partials.post-body');
        $view = $view->with('post_body', 'Foo');

        $this->assertSame('You are viewing post: Special Layout: Foo', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function views_with_errors_dont_print_output_to_the_client(): void
    {
        $view = $this->template_engine->make('bad-function');

        ob_start();

        try {
            $this->template_engine->renderView($view);
            $this->fail('The view should not be able to render.');
        } catch (ViewCantBeRendered $e) {
            $this->assertSame(
                "Error rendering view [bad-function].\nCaused by: Call to undefined function foo()",
                $e->getMessage()
            );
            $this->assertSame('', ob_get_clean());
        }
    }

    /**
     * @test
     */
    public function directories_are_searched_by_order_allowing_overwritten_of_view_names(): void
    {
        $engine = new TemplateEngine(
            new PHPViewFactory(
                $this->composers,
                [__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'view2', $this->view_dir]
            ),
        );

        $view = $engine->make('foo');
        $this->assertSame('foo modified', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function extended_parents_view_are_also_passed_through_view_composers(): void
    {
        $this->composers->addComposer('post-layout', fn (View $view): View => $view->with('sidebar', 'hi'));

        $view = $this->template_engine->make('partials.post-title');
        $view = $view->with('post_title', 'Foobar');
        $this->assertSame('You are viewing post: Foobar Our Sidebar: hi', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function errors_in_child_views_dont_print_output(): void
    {
        $this->expectException(ViewCantBeRendered::class);
        $this->expectExceptionMessage('Error rendering view [partials.with-error].');

        $view = $this->template_engine->make('partials.with-error');
        ob_start();

        try {
            $this->template_engine->renderView($view);
        } finally {
            $this->assertSame('', ob_get_clean());
        }
    }

    /**
     * @test
     */
    public function child_view_context_is_shared_with_parent_view(): void
    {
        $view = $this->template_engine->make('partials.share-var');
        $view = $view->with('child_var', 'BAZ');
        $this->assertSame('Var from child template: BAZ', $this->template_engine->renderView($view));
    }

    /**
     * @test
     */
    public function multiple_view_factories_can_be_used_together(): void
    {
        $view_engine = new TemplateEngine($this->php_view_factory, new TestTwigViewFactory());

        $php_view = $view_engine->make('foo.php');
        $this->assertSame('foo', $view_engine->renderView($php_view));

        $twig_view = $view_engine->make('test.twig');
        $this->assertSame('This is a twig view', $view_engine->renderView($twig_view));
    }

    /**
     * @test
     */
    public function test_exception_when_no_view_factory_can_render_a_view(): void
    {
        $view_engine = new TemplateEngine($this->php_view_factory, new TestTwigViewFactory());

        try {
            $view_engine->make('foo.xml');
        } catch (ViewNotFound $e) {
            $this->assertStringStartsWith(
                'None of the used view factories can render the any of the views [foo.xml]',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function test_make_with_multiple_views(): void
    {
        $view = $this->template_engine->make(['bogus1', 'foo']);
        $this->assertSame('foo', $view->name());

        $this->expectException(ViewNotFound::class);
        $this->template_engine->make(['bogus1', 'bogus2']);
    }

    /**
     * @test
     */
    public function test_render_with_multiple_views(): void
    {
        $content = $this->template_engine->render(['bogus1', 'foo']);
        $this->assertSame('foo', $content);

        $this->expectException(ViewNotFound::class);
        $this->template_engine->render(['bogus1', 'bogus2']);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_for_non_matching_view_class(): void
    {
        $view = new View('foo', FilePath::fromString(__DIR__ . '/fixtures/views/foo.php'), TestTwigViewFactory::class);
        $this->expectException(LogicException::class);
        $this->template_engine->renderView($view);
    }
}

final class TestTwigViewFactory implements ViewFactory
{
    public function make(string $view): View
    {
        if ('test.twig' !== $view) {
            throw new ViewNotFound('Can only render test.twig');
        }

        return new View($view, FilePath::fromString(__DIR__ . '/fixtures/views/test.twig'), self::class);
    }

    public function toString(View $view): string
    {
        if ('test.twig' !== $view->name()) {
            throw new ViewCantBeRendered('view name must be test.twig');
        }

        return (string) file_get_contents(__DIR__ . '/fixtures/views/test.twig');
    }
}
