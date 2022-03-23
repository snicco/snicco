<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Exception;

use RuntimeException;
use Throwable;

final class ViewNotFound extends RuntimeException
{
    public static function forView(string $view_name, ?Throwable $previous = null): ViewNotFound
    {
        $code = (null === $previous) ? 0 : (int) $previous->getCode();

        return new self(sprintf('The view [%s] could not be found.', $view_name), $code, $previous);
    }
}
