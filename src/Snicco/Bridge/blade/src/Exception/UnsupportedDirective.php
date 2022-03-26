<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Exception;

use BadMethodCallException;

use function sprintf;

final class UnsupportedDirective extends BadMethodCallException
{
    public function __construct(string $called_directive)
    {
        parent::__construct(sprintf(
            'The @%s directive is not supported as it requires the entire laravel framework.',
            $called_directive
        ));
    }
}
