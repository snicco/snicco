<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\View\View;

interface ViewFactory
{

    /**
     * @throws ViewNotFound
     */
    public function make(string $view): View;

}