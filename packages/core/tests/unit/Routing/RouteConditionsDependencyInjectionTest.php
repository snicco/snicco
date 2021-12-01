<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Conditions\ConditionWithDependency;

class RouteConditionsDependencyInjectionTest extends RoutingTestCase
{
    
    /** @test */
    public function a_condition_gets_dependencies_injected_after_the_passed_arguments()
    {
        $this->createRoutes(function () {
            $this->router->get('*', function () {
                return 'foo';
            })->where(ConditionWithDependency::class, true);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
    }
    
}

