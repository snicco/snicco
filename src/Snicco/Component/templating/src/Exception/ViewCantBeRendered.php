<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Exception;

use RuntimeException;
use Throwable;

final class ViewCantBeRendered extends RuntimeException
{
    public static function fromPrevious(string $view_name, Throwable $previous): ViewCantBeRendered
    {
        return new self(
            "Error rendering view [{$view_name}].\nCaused by: {$previous->getMessage()}",
            (int) $previous->getCode(),
            $previous
        );
    }
}
