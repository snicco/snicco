<?php

declare(strict_types=1);

use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

return function (Environment $env) {
    return new Kernel(
        new PimpleContainerAdapter(),
        $env,
        Directories::fromDefaults(__DIR__)
    );
};
