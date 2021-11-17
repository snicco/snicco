<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

interface MappedFilter extends Event, Mutable, IsForbiddenToWordPress
{
    
    /**
     * @return mixed
     */
    public function filterableAttribute();
    
}