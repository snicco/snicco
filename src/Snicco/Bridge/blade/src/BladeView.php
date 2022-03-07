<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\View\View;
use Throwable;

use function is_array;

/**
 * @psalm-internal Snicco\Bridge\Blade
 */
final class BladeView implements View
{

    private \Illuminate\View\View $illuminate_view;

    /**
     * @var array<string,mixed>
     */
    private array $context;

    public function __construct(\Illuminate\View\View $illuminate_view)
    {
        $this->illuminate_view = $illuminate_view;
        $this->context = $illuminate_view->getData();
    }

    public function name(): string
    {
        return $this->illuminate_view->name();
    }

    public function render(): string
    {
        try {
            $view = $this->cloneView();
            return $view->with($this->context)->render();
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
        $context = is_array($key) ? $key : [$key => $value];
        /**
         * @var mixed $value
         */
        foreach ($context as $key => $value) {
            $this->context[$key] = $value;
        }
    }

    public function context(): array
    {
        return $this->context;
    }

    public function path(): string
    {
        return $this->illuminate_view->getPath();
    }

    public function withContext(array $context): void
    {
        $this->context = $context;
    }

    private function cloneView(): \Illuminate\View\View
    {
        return new \Illuminate\View\View(
            $this->illuminate_view->getFactory(),
            $this->illuminate_view->getEngine(),
            $this->illuminate_view->getName(),
            $this->illuminate_view->getPath()
        );
    }

}