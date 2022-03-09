<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies;

final class Bar
{
    public string $value;

    public function __construct(string $value = 'bar')
    {
        $this->value = $value;
    }
}
