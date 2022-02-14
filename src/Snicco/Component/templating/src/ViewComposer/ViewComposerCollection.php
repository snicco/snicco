<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Closure;
use InvalidArgumentException;
use Snicco\Component\StrArr\Str;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\View\View;

/**
 * @api
 */
final class ViewComposerCollection
{

    private ViewComposerFactory $composer_factory;
    private GlobalViewContext $global_view_context;

    /**
     * @var array<array{views: list<string>, handler: class-string<ViewComposer>|Closure}>
     */
    private array $composers = [];

    public function __construct(
        ?ViewComposerFactory $composer_factory = null,
        ?GlobalViewContext $global_view_context = null
    ) {
        $this->composer_factory = $composer_factory ?? new NewableInstanceViewComposerFactory();
        $this->global_view_context = $global_view_context ?? new GlobalViewContext();
    }

    /**
     * @param string|list<string> $views
     * @param class-string<ViewComposer>|Closure(View) $composer
     *
     * @psalm-suppress DocblockTypeContradiction
     *
     */
    public function addComposer($views, $composer): void
    {
        $views = is_array($views) ? $views : [$views];

        if ($composer instanceof Closure) {
            $this->composers[] = [
                'views' => $views,
                'handler' => $composer,
            ];
            return;
        }

        if (!is_string($composer)) {
            throw new InvalidArgumentException(
                'A view composer has to be a closure or a class name.'
            );
        }

        if (!class_exists($composer)) {
            throw new InvalidArgumentException(
                "[$composer] is not a valid class."
            );
        }

        if (!in_array(ViewComposer::class, (array)class_implements($composer), true)) {
            throw new InvalidArgumentException(
                sprintf('Class [%s] does not implement [%s]', $composer, ViewComposer::class)
            );
        }

        $this->composers[] = [
            'views' => $views,
            'handler' => $composer,
        ];
    }

    /**
     * Composes the context the passed view in the following order.
     * => global context
     * => view composer context
     * => local context
     *
     * @interal
     *
     * @psalm-suppress MixedAssignment
     */
    public function compose(View $view): void
    {
        $local_context = $view->context();

        foreach ($this->global_view_context->get() as $name => $context) {
            $view->with($name, $context);
        }

        foreach ($this->matchingComposers($view) as $matchingComposer) {
            $c = $this->composer_factory->create($matchingComposer);
            $c->compose($view);
        }

        $view->with($local_context);
    }

    /**
     * @return list<Closure|class-string<ViewComposer>>
     */
    private function matchingComposers(View $view): array
    {
        $matching = [];
        $name = $view->name();

        foreach ($this->composers as $composer) {
            foreach ($composer['views'] as $matches_for_view) {
                if (Str::is($name, $matches_for_view)) {
                    $matching[] = $composer['handler'];
                }
            }
        }
        return $matching;
    }

}