<?php

declare(strict_types=1);

namespace Tests\HttpRouting\integration\Routing;

use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Core\EventDispatcher\Events\DoShutdown;

class RedirectRoutesTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_script_will_be_shut_down_if_a_redirect_route_matches()
    {
        $this->bootApp();
        
        $this->dispatcher->fake(DoShutdown::class);
        
        $this->get('/location-a')->assertRedirect('/location-b');
        
        $this->dispatcher->assertDispatched(function (DoShutdown $event) {
            return $event->do_shutdown === true;
        });
    }
    
}