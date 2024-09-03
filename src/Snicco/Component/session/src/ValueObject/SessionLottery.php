<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use Exception;
use LogicException;

use function random_int;

/**
 * @internal
 * @psalm-internal Snicco\Component\Session
 */
final class SessionLottery
{
    private int $percentage;

    public function __construct(int $percentage)
    {
        if ($percentage < 0) {
            throw new LogicException('The percentage can not be negative.');
        }

        if ($percentage > 100) {
            throw new LogicException('The percentage has to be between 0 and 100.');
        }

        $this->percentage = $percentage;
    }

    /**
     * @throws Exception
     */
    public function wins(): bool
    {
        return random_int(0, 99) < $this->percentage;
    }
}
