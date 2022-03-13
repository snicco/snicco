<?php

declare(strict_types=1);


namespace Snicco\Monorepo\GitHub;

use InvalidArgumentException;

use function sprintf;

final class AlreadyARepository extends InvalidArgumentException
{

    public function __construct(string $repo_url, string $package_full_name)
    {
        $message = sprintf('Package [%s] already has a GitHub repository at [%s].', $package_full_name, $repo_url);

        parent::__construct($message, 0);
    }

}