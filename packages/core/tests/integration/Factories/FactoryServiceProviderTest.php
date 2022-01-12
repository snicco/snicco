<?php

declare(strict_types=1);

namespace Tests\Core\integration\Factories;

use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Core\Factories\RouteActionFactory;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Core\Middleware\Internal\ControllerAction;
use Snicco\Core\Routing\Condition\RouteConditionFactory;

class FactoryServiceProviderTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->withAddedConfig('routing.controllers', [
                "Tests\\Core\\fixtures\\Controllers\\Web",
                "Tests\\Core\\fixtures\\Controllers\\Admin",
                "Tests\\Core\\fixtures\\Controllers\\Ajax",
            ]);
            $this->bootApp();
        });
        parent::setUp();
    }
    
    /** @test */
    public function the_factory_service_provider_is_set_up_correctly()
    {
        $this->assertInstanceOf(
            RouteActionFactory::class,
            TestApp::resolve(RouteActionFactory::class)
        );
        
        $this->assertInstanceOf(
            RouteConditionFactory::class,
            TestApp::resolve(RouteConditionFactory::class)
        );
    }
    
    /** @test */
    public function the_controller_namespace_can_be_configured_correctly()
    {
        /** @var RouteActionFactory $factory */
        $factory = TestApp::resolve(RouteActionFactory::class);
        
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->create('AdminController@handle')
        );
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->create('WebController@handle')
        );
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->create('AjaxController@handle')
        );
    }
    
}