<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Closure;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\ValueObject\View;

final class ViewComposerCollection
{
    private ViewComposerFactory $composer_factory;

    private GlobalViewContext $global_view_context;

    /**
     * @var array<array{views: array<string>, handler: class-string<ViewComposer>|Closure(View):View}>
     */
    private array $composers = [];

    public function __construct(
        ?ViewComposerFactory $composer_factory = null,
        ?GlobalViewContext $global_view_context = null
    ) {
        $this->composer_factory = $composer_factory ?: new NewableInstanceViewComposerFactory();
        $this->global_view_context = $global_view_context ?: new GlobalViewContext();
    }

    /**
     * @param string|string[]                               $views
     * @param class-string<ViewComposer>|Closure(View):View $composer
     */
    public function addComposer($views, $composer): void
    {
        $views = Arr::toArray($views);

        $this->composers[] = [
            'views' => $views,
            'handler' => $composer,
        ];
    }

    /**
     * Composes the context the passed view in the following order. => global
     * context => view composer context => local context.
     */
    public function compose(View $view): View
    {
        $local_context = $view->context();

        /**
         * @var mixed $context
         */
        foreach ($this->global_view_context->get() as $name => $context) {
            $view = $view->with($name, $context);
        }

        foreach ($this->matchingComposers($view) as $composer) {
            $view = $composer->compose($view);
        }

        return $view->with($local_context);
    }

    /**
     * @return ViewComposer[]
     */
    private function matchingComposers(View $view): array
    {
        $matching = [];
        $name = $view->name();

        foreach ($this->composers as $composer) {
            foreach ($composer['views'] as $matches_for_view) {
                if (Str::is($name, $matches_for_view)) {
                    $handler = $composer['handler'];
                    $matching[] = $handler instanceof Closure
                        ? new ClosureViewComposer($handler)
                        : $this->composer_factory->create($handler);
                }
            }
        }

        return $matching;
    }
}
