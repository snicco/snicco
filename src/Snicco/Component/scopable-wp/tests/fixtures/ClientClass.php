<?php

declare(strict_types=1);

namespace Snicco\Component\ScopableWP\Tests\fixtures;

use Snicco\Component\ScopableWP\ScopableWP;

final class ClientClass
{

    private ScopableWP $wp;

    public function __construct(ScopableWP $wp)
    {
        $this->wp = $wp;
    }

    public function getSomething(string $key): int
    {
        return $this->wp->cacheGet($key);
    }

}