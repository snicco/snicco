<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Dispatcher\WordPressDispatcher;

/**
 * If you dispatch an event as a string instead of using a dedicated class
 * the event will be transformed into a GenericEvent.
 * Assuming you would call $dispatcher->dispatch('foo_event', 'bar', ['baz', 'biz]'):
 * A listener would receive the three arguments in this order. ('bar', ['baz','biz'], 'foo_event').
 * Events you dispatch as a strings ARE NOT MUTABLE by default and thus can not be filtered.
 * They can only be used as an action. You can customize this behaviour by passing
 * a different implementation of "EventSharing" into the dispatcher.
 *
 * @see WordPressDispatcher
 * @see GenericEventParser::transformToEvent()
 * @api
 */
final class GenericEvent implements Event
{
    
    /**
     * @var array
     */
    private $arguments;
    
    /**
     * @var string
     */
    private $name;
    
    public function __construct(string $name, array $arguments)
    {
        $this->arguments = $arguments;
        $this->name = $name;
    }
    
    public function getPayload() :array
    {
        return $this->arguments;
    }
    
    public function getName() :string
    {
        return $this->name;
    }
    
}