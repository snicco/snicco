<?php

declare(strict_types=1);

namespace Snicco\View\Contracts;

/**
 * @api
 */
interface ViewComposer
{
    
    /**
     * Add context values to the passed view
     *
     * @param  ViewInterface  $view
     */
    public function compose(ViewInterface $view) :void;
    
}