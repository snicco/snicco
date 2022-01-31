<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Exception;

use Throwable;
use RuntimeException;

final class UnavailableStorage extends RuntimeException
{
    
    public static function fromPrevious(Throwable $e) :UnavailableStorage
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }
    
}