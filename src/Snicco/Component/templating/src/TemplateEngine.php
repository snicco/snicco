<?php

declare(strict_types=1);

namespace Snicco\Component\Templating;

use InvalidArgumentException;
use LogicException;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\ValueObject\View;
use Snicco\Component\Templating\ViewFactory\ViewFactory;

use function get_class;
use function implode;
use function sprintf;

final class TemplateEngine
{
    /**
     * @var array<class-string<ViewFactory>, ViewFactory>
     */
    private array $class_map = [];

    public function __construct(ViewFactory ...$view_factories)
    {
        if (empty($view_factories)) {
            throw new InvalidArgumentException('At least one instance of ViewFactory must be passed.');
        }

        foreach ($view_factories as $view_factory) {
            $this->class_map[get_class($view_factory)] = $view_factory;
        }
    }

    /**
     * Renders a view's content as a string.
     *
     * @param string|string[] $view_name An absolute path or a view identifier where dots indicate directory traversal.
     * @param array<string, mixed> $context
     *
     * @throws ViewCantBeRendered
     * @throws ViewNotFound
     */
    public function render($view_name, array $context = []): string
    {
        $view = $this->make($view_name);
        $view = $view->with($context);

        return $this->renderView($view);
    }

    public function renderView(View $view): string
    {
        $factory = $this->class_map[$view->viewFactoryClass()] ?? null;
        if (null === $factory) {
            throw new LogicException(
                sprintf(
                    'No instance of [%s] is available. This can only happen if you manually created an invalid view.',
                    $view->viewFactoryClass()
                )
            );
        }
        return $factory->toString($view);
    }

    /**
     * Creates an instance of View for the first existing $view_name
     *
     * @param string|string[] $view_name An absolute path or a view identifier where dots indicate directory traversal.
     *
     * @throws ViewNotFound when no view can be created with any view factory
     */
    public function make($view_name): View
    {
        return $this->createFirstMatchingView(Arr::toArray($view_name));
    }

    /**
     * @param string[] $views
     *
     * @throws ViewNotFound
     */
    private function createFirstMatchingView(array $views): View
    {
        foreach ($views as $view) {
            foreach ($this->class_map as $view_factory) {
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
                implode("\n", array_map(fn(ViewFactory $v) => get_class($v), $this->class_map))
            )
        );
    }
}
