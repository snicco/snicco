<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventMapping;

/**
 * @api
 */
interface DispatchesConditionally
{
    
    public function shouldDispatch() :bool;
    
}