<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\fixtures;

use RuntimeException;

final class SlowDown extends RuntimeException
{
    
}