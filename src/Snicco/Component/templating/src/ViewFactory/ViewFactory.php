<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\View\View;

/**
 * @api
 */
interface ViewFactory
{

    /**
     * Create the first view that matches the array of passed views and throw an exception if no
     * view can be created.
     *
     * @throws ViewNotFound
     */
    public function make(string $view): View;

}