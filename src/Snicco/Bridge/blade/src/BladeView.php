<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\View\View;
use Throwable;

/**
 *
 * @psalm-internal Snicco\Bridge\Blade
 *
 * @internal
 */
final class BladeView implements View, \Illuminate\Contracts\View\View
{

    private \Illuminate\View\View $illuminate_view;

    public function __construct(\Illuminate\View\View $illuminate_view)
    {
        $this->illuminate_view = $illuminate_view;
    }

    public function render(): string
    {
        return $this->toString();
    }

    public function name(): string
    {
        return $this->illuminate_view->name();
    }

    /**
     * Add a piece of data to the view.
     *
     * @param string|array $key
     * @param mixed $value
     *
     * @return $this
     */
    public function with($key, $value = null): View
    {
        $this->illuminate_view->with($key, $value);
        return $this;
    }

    public function getData(): array
    {
        return $this->context();
    }

    public function toString(): string
    {
        try {
            return $this->illuminate_view->render();
        } catch (Throwable $e) {
            throw new ViewCantBeRendered(
                "Error rendering view:[{$this->name()}]\nCaused by: {$e->getMessage()}",
                (int)$e->getCode(),
                $e,
            );
        }
    }

    public function context(): array
    {
        /** @var array<string,mixed> $data */
        $data = $this->illuminate_view->getData();
        return $data;
    }

    public function path(): string
    {
        return $this->illuminate_view->getPath();
    }

}