<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\View;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;

interface View
{
    /**
     * Render the view to a string.
     *
     * @throws ViewCantBeRendered If any occurs during rendering
     */
    public function render(): string;

    /**
     * Takes the provided context and returns a NEW instance that now has the merged context.
     *
     * @param array<string, mixed>|string $key
     * @param mixed                       $value
     *
     * @return static
     *
     * @psalm-mutation-free
     */
    public function with($key, $value = null): self;

    /**
     * @return array<string,mixed>
     *
     * @psalm-mutation-free
     */
    public function context(): array;

    /**
     * @psalm-mutation-free
     */
    public function name(): string;

    /**
     * Returns The full local path of the view.
     *
     * @psalm-mutation-free
     */
    public function path(): string;
}
