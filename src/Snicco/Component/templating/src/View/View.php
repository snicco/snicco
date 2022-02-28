<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\View;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;

interface View
{

    /**
     * Render the view to a string.
     *
     * @throws ViewCantBeRendered If any kind of error occurs.
     */
    public function render(): string;

    /**
     * Add (merge) context to the view object.
     *
     * @param string|array<string, mixed> $key
     * @param mixed $value
     */
    public function addContext($key, $value = null): void;

    /**
     * Replaces the current context with the provided context
     *
     * @param array<string,mixed> $context
     */
    public function withContext(array $context): void;

    /**
     * @return array<string,mixed>
     */
    public function context(): array;

    public function name(): string;

    /**
     * @return string The full local path of the view.
     */
    public function path(): string;

}
