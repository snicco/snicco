<?php

declare(strict_types=1);

namespace Tests\integration\Routing;

use Snicco\Events\Event;
use Tests\FrameworkTestCase;
use Snicco\Events\ResponseSent;
use Snicco\Http\Responses\RedirectResponse;

class RedirectRoutesTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_script_will_be_shut_down_if_a_redirect_route_matches()
    {
        
        Event::fake([ResponseSent::class]);
        
        $this->get('/location-a')->assertRedirect('/location-b');
        
        Event::assertDispatched(function (ResponseSent $event) {
            
            return $event->response instanceof RedirectResponse;
            
        });
        
    }
    
}