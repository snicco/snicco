<?php

declare(strict_types=1);

namespace Tests\Core\unit\Application;

use LogicException;
use BadMethodCallException;
use Snicco\Application\Application;
use Tests\Codeception\shared\UnitTest;
use Snicco\Application\ApplicationTrait;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class ApplicationTraitTest extends UnitTest
{
    
    private string $base_path;
    
    protected function setUp() :void
    {
        $this->base_path = __DIR__;
        parent::setUp();
    }
    
    protected function tearDown() :void
    {
        FooApp::setApplication(null);
        BarApp::setApplication(null);
        
        parent::tearDown();
    }
    
    /** @test */
    public function a_new_app_instance_can_be_created()
    {
        $app = FooApp::make($this->base_path);
        $this->assertInstanceOf(Application::class, $app);
    }
    
    /** @test */
    public function an_application_can_not_be_created_twice()
    {
        $app = FooApp::make($this->base_path);
        $this->assertInstanceOf(Application::class, $app);
        
        try {
            $app = FooApp::make($this->base_path);
            $this->fail("App created twice.");
        } catch (LogicException $e) {
            $this->assertStringStartsWith(
                sprintf("Application already created for class [%s].", FooApp::class),
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function multiple_app_instances_can_exists_independently()
    {
        $this->assertInstanceOf(Application::class, $foo = FooApp::make($this->base_path));
        $this->assertInstanceOf(Application::class, $bar = BarApp::make($this->base_path));
        
        $this->assertNotSame($foo, $bar);
    }
    
    /** @test */
    public function exceptions_get_thrown_when_trying_to_call_a_method_on_a_non_instantiated_application_instance()
    {
        $this->expectExceptionMessage('Application instance not created');
        $this->expectException(ConfigurationException::class);
        
        FooApp::foo();
    }
    
    /** @test */
    public function exceptions_get_thrown_when_trying_to_call_a_non_callable_method()
    {
        $this->expectExceptionMessage('does not exist');
        $this->expectException(BadMethodCallException::class);
        
        FooApp::make($this->base_path);
        FooApp::badMethod();
    }
    
    /** @test */
    public function static_method_calls_get_forwarded_to_the_application_with()
    {
        FooApp::make($this->base_path);
        FooApp::alias('application_method', fn($foo, $bar, $baz) => $foo.$bar.$baz);
        
        $this->assertSame('foobarbaz', FooApp::application_method('foo', 'bar', 'baz'));
    }
    
    /** @test */
    public function the_application_trait_is_bound()
    {
        $app = FooApp::make($this->base_path);
        
        $this->assertSame(FooApp::class, $app->resolve(ApplicationTrait::class));
    }
    
}

class FooApp
{
    
    use ApplicationTrait;
}

class BarApp
{
    
    use ApplicationTrait;
}