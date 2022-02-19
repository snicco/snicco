<?php

declare(strict_types=1);

namespace Snicco\Component\Session\EventDispatcher;

final class NullSessionDispatcher implements SessionEventDispatcher
{

    public function dispatchAll(array $events): void
    {
        //
    }

}