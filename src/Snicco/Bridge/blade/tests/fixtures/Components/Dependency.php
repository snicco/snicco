<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;
use Snicco\Bridge\Blade\Tests\fixtures\TestDependencies\Foo;

final class Dependency extends BladeComponent
{
    public Foo $foo;

    public string $message;

    /**
     * @var array
     */
    protected $except = ['foo'];

    public function __construct(Foo $foo, string $message)
    {
        $this->foo = $foo;
        $this->message = $foo->value . $message;
    }

    public function render(): string
    {
        return $this->componentName('components.with-dependency');
    }
}
