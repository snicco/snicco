<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Snicco\Component\Templating\View\View;

/**
 * @api
 */
interface ViewComposer
{
    
    /**
     * Add context values to the passed view
     *
     * @param  View  $view
     */
    public function compose(View $view) :void;
    
}