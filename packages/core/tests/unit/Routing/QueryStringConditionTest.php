<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Codeception\shared\UnitTest;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Routing\AdminDashboard\AdminDashboard;
use Snicco\Core\Routing\Condition\QueryStringCondition;
use Snicco\Core\Routing\AdminDashboard\WPAdminDashboard;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

final class QueryStringConditionTest extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreatePsrRequests;
    
    /** @test */
    public function test_is_satisfied_can_pass()
    {
        $request = $this->frontendRequest('GET', '/foo?bar=baz');
        
        $condition = new QueryStringCondition(['bar' => 'baz']);
        
        $this->assertTrue($condition->isSatisfied($request));
    }
    
    /** @test */
    public function test_is_satisfied_can_fail()
    {
        $request = $this->frontendRequest('GET', '/static?foo=bar&baz=biz');
        
        $condition = new QueryStringCondition(['foo' => 'bar', 'baz' => 'boo']);
        
        $this->assertFalse($condition->isSatisfied($request));
    }
    
    /** @test */
    public function test_get_arguments()
    {
        $request = $this->frontendRequest('GET', '/static?foo=bar&baz=biz&bang=boo');
        
        $condition = new QueryStringCondition(['foo' => 'bar', 'baz' => 'biz']);
        
        // Boo not present
        $this->assertSame([
            'bar',
            'biz',
        ], $condition->getArguments($request));
    }
    
    protected function adminDashboard() :AdminDashboard
    {
        return WPAdminDashboard::fromDefaults();
    }
    
}