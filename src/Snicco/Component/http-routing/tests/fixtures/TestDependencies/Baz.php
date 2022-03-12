<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies;

final class Baz
{
    public string $value;

    public function __construct(string $value = 'baz')
    {
        $this->value = $value;
    }
}
