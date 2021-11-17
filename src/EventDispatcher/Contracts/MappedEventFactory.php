<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Snicco\EventDispatcher\MappedEventCreationException;

/**
 * @api
 */
interface MappedEventFactory
{
    
    /**
     * @param  string  $event_class
     * @param  array  $wordpress_hook_arguments
     *
     * @return MappedAction|MappedFilter
     * @throws MappedEventCreationException
     */
    public function make(string $event_class, array $wordpress_hook_arguments) :Event;
    
}