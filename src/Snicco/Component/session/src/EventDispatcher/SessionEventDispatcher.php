<?php

declare(strict_types=1);

namespace Snicco\Component\Session\EventDispatcher;

interface SessionEventDispatcher
{

    /**
     * @param object[] $events
     */
    public function dispatchAll(array $events): void;

}