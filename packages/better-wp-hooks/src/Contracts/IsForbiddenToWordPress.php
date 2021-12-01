<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * By default, all events will be passed first through your registered listeners in the dispatcher.
 * After that they are either passed to WordPress using the do_action function or the apply_filters
 * function depending on whether they are mutable or not. If you use this interface on your
 * event class the event will not be passed to the WordPress API after your internal listeners
 * have been called. This is a great way to make some of your events internal.
 *
 * @api
 */
interface IsForbiddenToWordPress
{
    
}