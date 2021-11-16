<?php

declare(strict_types=1);

namespace Snicco\Traits;

trait HasLottery
{
    
    protected function hitsLottery(array $lottery_config) :bool
    {
        $value = random_int(1, $lottery_config[1]);
        
        return $value <= $lottery_config[0];
    }
    
}