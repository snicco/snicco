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

    private array $composers = [];

    private ViewComposerFactory $composer_factory;

    /**
     * @var GlobalViewContext The contents that will be available to every view.
     */
    private GlobalViewContext $global_view_context;

    public function __construct(
        ?ViewComposerFactory $composer_factory = null,
        ?GlobalViewContext $global_view_context = null
    ) {
        $this->composer_factory = $composer_factory ?? new NewableInstanceViewComposerFactory();
        $this->global_view_context = $global_view_context ?? new GlobalViewContext();
    }

    /**
     * @param string|string[] $views
     * @param string|Closure class name or closure
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

        if (!in_array(ViewComposer::class, class_implements($composer), true)) {
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
     */
    public function compose(View $view): void
    {
        $local_context = $view->context();

        foreach ($this->global_view_context->get() as $name => $context) {
            $view->with($name, $context);
        }

        $composers = $this->matchingComposers($view);

        array_walk($composers, function ($composer) use ($view) {
            $c = $this->composer_factory->create($composer);
            $c->compose($view);
        });

        $view->with($local_context);
    }

    private function matchingComposers(View $view): array
    {
        $matching = [];
        $name = $view->name();

        foreach ($this->composers as $composer) {
            foreach ($composer['views'] as $matches_for_view) {
                if (Str::is($matches_for_view, $name)) {
                    $matching[] = $composer['handler'];
                }
            }
        }
        return $matching;
    }

}