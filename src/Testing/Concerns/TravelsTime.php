<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

trait TravelsTime
{
    
    protected function backToPresent()
    {
        
        if (class_exists(Carbon::class)) {
            Carbon::setTestNow();
        }
        
        if (class_exists(CarbonImmutable::class)) {
            CarbonImmutable::setTestNow();
        }
        
    }
    
    /** Time travel is always cumulative */
    protected function travelIntoFuture(int $seconds)
    {
        
        Carbon::setTestNow(Carbon::now()->addSeconds($seconds));
        
    }
    
}