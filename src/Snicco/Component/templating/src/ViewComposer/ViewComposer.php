<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Snicco\Component\Templating\View\View;

interface ViewComposer
{
    /**
     * Add context values to the passed view.
     *
     * @template  T of View
     *
     * @param T $view
     *
     * @return T
     */
    public function compose(View $view): View;
}
