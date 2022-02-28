<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\View\View;
use Throwable;

/**
 * @psalm-internal Snicco\Bridge\Blade
 */
final class BladeView implements View, \Illuminate\Contracts\View\View
{

    private \Illuminate\View\View $illuminate_view;

    public function __construct(\Illuminate\View\View $illuminate_view)
    {
        $this->illuminate_view = $illuminate_view;
    }

    public function name(): string
    {
        return $this->illuminate_view->name();
    }

    public function getData(): array
    {
        return $this->context();
    }

    public function with($key, $value = null)
    {
        $this->illuminate_view->with($key, $value);
        return $this;
    }

    public function render(): string
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

    public function addContext($key, $value = null): void
    {
        $this->with($key, $value);
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

    public function withContext(array $context): void
    {
        $this->illuminate_view = new \Illuminate\View\View(
            $this->illuminate_view->getFactory(),
            $this->illuminate_view->getEngine(),
            $this->illuminate_view->name(),
            $this->illuminate_view->getPath()
        );
        $this->illuminate_view->with($context);
    }
}