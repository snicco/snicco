<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventMapping;

use Snicco\Component\EventDispatcher\Event;

/**
 * Use this interface if you want to map your event to a WordPress action.
 *
 * @api
 */
interface MappedAction extends Event, DispatchesConditionally
{

}