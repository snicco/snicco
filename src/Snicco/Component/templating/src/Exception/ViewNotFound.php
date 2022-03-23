<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Exception;

use RuntimeException;
use Throwable;

final class ViewNotFound extends RuntimeException
{
    public static function forView(string $view_name, ?Throwable $previous = null): ViewNotFound
    {
        $code = ($previous === null) ? 0 : (int)$previous->getCode();
        return new self("The view [$view_name] could not be found.", $code, $previous);
    }
}
