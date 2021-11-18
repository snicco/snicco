<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * @api
 * Use this interface if you want to map your event to a WordPress filter.
 */
interface MappedFilter extends Event, Mutable, IsForbiddenToWordPress
{
    
    /**
     * @return mixed
     */
    public function filterableAttribute();
    
}