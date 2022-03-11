<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel;

interface Bundle extends Bootstrapper
{
    /**
     * The alias of your bundle is used in various places during the
     * bootstrapping process. Aliases MUST BE UNIQUE per application and MUST be
     * considered part of the public API that a bundle offers. Changing the
     * bundle alias IS A MAYOR BC break. As a best practices the bundle alias
     * should be set to the composer identifier on packagist.
     */
    public function alias(): string;
}
