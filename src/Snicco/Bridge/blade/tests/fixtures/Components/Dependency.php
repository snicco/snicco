<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;
use Snicco\Bridge\Blade\Tests\fixtures\TestDependencies\Foo;
use Snicco\Component\Templating\View\View;

class Dependency extends BladeComponent
{

    public Foo $foo;
    public string $message;

    /**
     * @var string[]
     */
    protected $except = ['foo'];

    public function __construct(Foo $foo, $message)
    {
        $this->foo = $foo;
        $this->message = $foo->value . $message;
    }

    public function render(): View
    {
        return $this->view('components.with-dependency');
    }

}