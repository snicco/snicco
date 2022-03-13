<?php

declare(strict_types=1);


namespace Snicco\Monorepo\Packagist;

use InvalidArgumentException;

use function sprintf;

final class AlreadyAtPackagist extends InvalidArgumentException
{

    public function __construct(string $package_name, string $package_url)
    {
        $message = sprintf('The package [%s] is already created at [%s].', $package_name, $package_url);
        parent::__construct($message, 0);
    }

}