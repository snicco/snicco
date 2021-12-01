<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Support\Carbon;

trait TravelsTime
{
    
    protected function backToPresent()
    {
        if (class_exists(Carbon::class)) {
            Carbon::setTestNow();
        }
    }
    
    /** Time travel is always cumulative */
    protected function travelIntoFuture(int $seconds)
    {
        Carbon::setTestNow(Carbon::now()->addSeconds($seconds));
    }
    
}