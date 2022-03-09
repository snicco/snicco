<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl;

use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Webmozart\Assert\Assert;

use function random_int;

final class GarbageCollector
{
    public static function clean(SignedUrlStorage $storage, int $percentage): void
    {
        Assert::range($percentage, 0, 100);
        if (random_int(0, 99) < $percentage) {
            $storage->gc();
        }
    }
}
