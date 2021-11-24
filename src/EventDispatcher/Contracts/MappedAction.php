<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * Use this interface if you want to map your event to a WordPress action.
 *
 * @api
 */
interface MappedAction extends Event, IsForbiddenToWordPress, DispatchesConditionally
{

}