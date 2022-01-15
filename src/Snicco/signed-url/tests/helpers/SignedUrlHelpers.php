<?php

declare(strict_types=1);

namespace Tests\SignedUrl\helpers;

use Snicco\SignedUrl\SignedUrl;

trait SignedUrlHelpers
{
    
    protected function createMagicLink(string $link_target, string $signature, int $expires_in = 10, $max_usage = 1) :SignedUrl
    {
        return SignedUrl::create(
            $link_target,
            $link_target,
            $signature,
            time() + $expires_in,
            $max_usage
        );
    }
    
}