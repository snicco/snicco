<?php

declare(strict_types=1);

namespace Tests\View\fixtures\ViewComposers;

use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use Tests\Codeception\shared\TestDependencies\Bar;

class FooComposer implements ViewComposer
{

    private Bar $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function compose(View $view): void
    {
        $view->with(['foo' => $this->bar->bar]);
    }

}