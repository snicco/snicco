<?php

declare(strict_types=1);

namespace Snicco\Component\WPObjectCachePsr16;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrCacheInvalidArgument;

final class BadKey extends InvalidArgumentException implements PsrCacheInvalidArgument
{
    
}