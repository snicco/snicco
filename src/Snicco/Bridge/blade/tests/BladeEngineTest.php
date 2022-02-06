<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Snicco\Bridge\Blade\BladeView;
use Snicco\Component\Templating\Exception\ViewNotFound;

class BladeEngineTest extends BladeTestCase
{

    /**
     * @test
     */
    public function the_blade_factory_can_create_a_blade_view(): void
    {
        $this->assertInstanceOf(BladeView::class, $this->view_engine->make('foo'));
    }

    /**
     * @test
     */
    public function exceptions_get_caught_and_translated(): void
    {
        $this->expectException(ViewNotFound::class);

        $this->assertInstanceOf(BladeView::class, $this->view_engine->make('bogus'));
    }

}