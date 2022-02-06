<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Closure;
use RuntimeException;
use Snicco\Component\Templating\OutputBuffer;

/**
 * @internal
 */
final class ChildContent
{

    private Closure $content;

    public function __construct(Closure $content)
    {
        $this->content = $content;
    }

    /**
     * @throws RuntimeException __toString can throw exceptions in 7.4+
     */
    public function __toString()
    {
        OutputBuffer::start();
        call_user_func($this->content);
        return OutputBuffer::get();
    }

}