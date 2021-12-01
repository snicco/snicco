<?php

declare(strict_types=1);

namespace Tests\Core\integration\Routing;

use Snicco\EventDispatcher\Events\DoShutdown;
use Tests\Codeception\shared\FrameworkTestCase;

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