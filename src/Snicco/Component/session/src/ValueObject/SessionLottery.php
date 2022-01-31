<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use LogicException;

use function random_int;

/**
 * @interal
 */
final class SessionLottery
{
    
    private int $percentage;
    
    public function __construct(int $percentage)
    {
        if ($percentage < 0) {
            throw new LogicException(
                "The percentage can not be negative."
            );
        }
        
        if ($percentage > 100) {
            throw new LogicException(
                'The percentage has to be between 0 and 100.'
            );
        }
        
        $this->percentage = $percentage;
    }
    
    public function wins() :bool
    {
        return random_int(0, 99) < $this->percentage;
    }
    
}