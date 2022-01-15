<?php

declare(strict_types=1);

namespace Snicco\SignedUrl;

use Webmozart\Assert\Assert;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;

use function random_int;

final class GarbageCollector
{
    
    public static function clean(SignedUrlStorage $storage, int $percentage)
    {
        Assert::range($percentage, 0, 100);
        if (random_int(0, 99) < $percentage) {
            $storage->gc();
        }
    }
    
}