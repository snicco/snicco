<?php

declare(strict_types=1);

namespace Snicco\Component\Templating;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewFactory\ViewFactory;

use function get_class;
use function implode;

final class ViewEngine
{
    /**
     * @var ViewFactory[]
     */
    private array $view_factories;

    public function __construct(ViewFactory ...$view_factories)
    {
        $this->view_factories = $view_factories;
    }

    /**
     * Renders a view's content as a string.
     *
     * @param string|string[]      $view
     * @param array<string, mixed> $context
     *
     * @throws ViewNotFound
     * @throws ViewCantBeRendered
     */
    public function render($view, array $context = []): string
    {
        $view = $this->make($view)->with($context);

        return $view->render();
    }

    /**
     * @param string|string[] $view
     *
     * @throws ViewNotFound when no view can be created with any view factory
     */
    public function make($view): View
    {
        return $this->createFirstMatchingView((array) $view);
    }

    /**
     * @param string[] $views
     *
     * @throws ViewNotFound
     */
    private function createFirstMatchingView(array $views): View
    {
        foreach ($views as $view) {
            foreach ($this->view_factories as $view_factory) {
                try {
                    return $view_factory->make($view);
                } catch (ViewNotFound $e) {
                }
            }
        }

        throw new ViewNotFound(
            sprintf(
                "None of the used view factories can render the any of the views [%s].\nTried with:\n%s",
                implode(',', $views),
                implode(
                    "\n",
                    array_map(function (ViewFactory $v) {
                        return get_class($v);
                    }, $this->view_factories)
                )
            )
        );
    }
}
