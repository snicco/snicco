<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Controller;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Foo;

final class ControllerWithDependencies
{
    private Foo $foo;

    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }

    public function __invoke(Request $request): string
    {
        return $this->foo->value . '_controller';
    }
}
