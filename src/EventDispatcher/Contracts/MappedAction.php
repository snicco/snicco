<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * @api
 * Use this interface if you want to map your event to a WordPress action.
 */
interface MappedAction extends Event, IsForbiddenToWordPress
{

}