<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Tests\stubs\TestApp;
use Tests\FrameworkTestCase;
use Snicco\View\ViewComposer;
use Snicco\Routing\ControllerAction;
use Snicco\Factories\ConditionFactory;
use Snicco\Factories\RouteActionFactory;
use Snicco\Factories\ViewComposerFactory;

class FactoryServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_factory_service_provider_is_set_up_correctly()
    {
        
        $this->assertInstanceOf(
            RouteActionFactory::class,
            TestApp::resolve(RouteActionFactory::class)
        );
        $this->assertInstanceOf(
            ViewComposerFactory::class,
            TestApp::resolve(ViewComposerFactory::class)
        );
        $this->assertInstanceOf(ConditionFactory::class, TestApp::resolve(ConditionFactory::class));
        
    }
    
    /** @test */
    public function the_controller_namespace_can_be_configured_correctly()
    {
        
        /** @var RouteActionFactory $factory */
        $factory = TestApp::resolve(RouteActionFactory::class);
        
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->createUsing('AdminController@handle')
        );
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->createUsing('WebController@handle')
        );
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->createUsing('AjaxController@handle')
        );
        
    }
    
    /** @test */
    public function the_view_composer_namespace_can_be_configured_correctly()
    {
        
        /** @var ViewComposerFactory $factory */
        $factory = TestApp::resolve(ViewComposerFactory::class);
        
        $this->assertInstanceOf(ViewComposer::class, $factory->createUsing('FooComposer@compose'));
        
    }
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->bootApp();
        });
        parent::setUp();
    }
    
}