<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Closure;
use Snicco\Component\Templating\OutputBuffer;

/**
 * @psalm-internal Snicco\Component\Templating
 */
final class ChildContent
{
    private Closure $content;

    public function __construct(Closure $content)
    {
        $this->content = $content;
    }

    public function __invoke(): string
    {
        OutputBuffer::start();
        ($this->content)();

        return OutputBuffer::get();
    }

    public function __toString(): string
    {
        return $this->__invoke();
    }
}
