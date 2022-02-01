<?php

declare(strict_types=1);

namespace Snicco\Component\Templating;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewFactory\ViewFactory;

/**
 * @api
 */
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
     * @param array<string, mixed> $context
     *
     * @throws ViewNotFound
     * @throws ViewCantBeRendered
     */
    public function render(string $view, array $context = []): string
    {
        $view = $this->make($view)->with($context);

        return $view->toString();
    }

    /**
     * @throws ViewNotFound When no view can be created with any view factory.
     */
    public function make(string $view): View
    {
        $view = $this->createView($view);

        return $view->with('view', $this);
    }

    /**
     * @throws ViewNotFound
     */
    private function createView(string $view): View
    {
        foreach ($this->view_factories as $view_factory) {
            try {
                return $view_factory->make($view);
            } catch (ViewNotFound $e) {
                //
            }
        }

        throw new ViewNotFound(
            sprintf(
                "None of the used view factories can render the view [%s].\nTried with:\n%s",
                $view,
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
