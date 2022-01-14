<?php

declare(strict_types=1);

namespace Tests\Core\unit\Application;

use Snicco\Core\Environment;
use InvalidArgumentException;
use Tests\Codeception\shared\UnitTest;

final class EnvironmentTest extends UnitTest
{
    
    /** @test */
    public function test_from_string()
    {
        $app_env = Environment::fromString('prod');
        
        $this->assertInstanceOf(Environment::class, $app_env);
    }
    
    /** @test */
    public function test_exception_if_empty_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'App environment can not be constructed with an empty string.'
        );
        $app_env = Environment::fromString('');
    }
    
    /** @test */
    public function test_exception_if_not_a_valid_environment()
    {
        $app_env = Environment::fromString('prod');
        $app_env = Environment::fromString('testing');
        $app_env = Environment::fromString('dev');
        $app_env = Environment::fromString('staging');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'App environment has to be one of [testing,prod,dev,staging]. Got: [local]'
        );
        
        $app_env = Environment::fromString('local');
    }
    
    /** @test */
    public function test_as_string()
    {
        $app_env = Environment::fromString('prod');
        $this->assertSame('prod', $app_env->asString());
        
        $app_env = Environment::fromString('testing');
        $this->assertSame('testing', $app_env->asString());
        
        $app_env = Environment::fromString('dev');
        $this->assertSame('dev', $app_env->asString());
        
        $app_env = Environment::fromString('staging');
        $this->assertSame('staging', $app_env->asString());
    }
    
    /** @test */
    public function test_named_constructors()
    {
        $app_env = Environment::prod();
        $this->assertSame('prod', $app_env->asString());
        
        $app_env = Environment::testing();
        $this->assertSame('testing', $app_env->asString());
        
        $app_env = Environment::dev();
        $this->assertSame('dev', $app_env->asString());
        
        $app_env = Environment::staging();
        $this->assertSame('staging', $app_env->asString());
    }
    
    /** @test */
    public function test_is_environment()
    {
        $env = Environment::prod();
        $this->assertTrue($env->isProduction());
        $this->assertFalse($env->isDevelop());
        $this->assertFalse($env->isTesting());
        $this->assertFalse($env->isStaging());
        
        $env = Environment::dev();
        $this->assertTrue($env->isDevelop());
        $this->assertFalse($env->isProduction());
        $this->assertFalse($env->isTesting());
        $this->assertFalse($env->isStaging());
        
        $env = Environment::testing();
        $this->assertTrue($env->isTesting());
        $this->assertFalse($env->isProduction());
        $this->assertFalse($env->isDevelop());
        $this->assertFalse($env->isStaging());
        
        $env = Environment::staging();
        $this->assertTrue($env->isStaging());
        $this->assertFalse($env->isProduction());
        $this->assertFalse($env->isDevelop());
        $this->assertFalse($env->isTesting());
    }
    
    /** @test */
    public function test_debug_is_not_allowed_in_production()
    {
        $app_env = Environment::prod();
        $this->assertFalse($app_env->isDebug());
        
        $app_env = Environment::fromString('prod');
        $this->assertFalse($app_env->isDebug());
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "App environment can not be in debug mode while in production."
        );
        $app_env = Environment::fromString('prod', true);
    }
    
    /** @test */
    public function test_debug_can_be_enabled_in_other_envs()
    {
        $app_env = Environment::fromString('dev');
        $this->assertFalse($app_env->isDebug());
        $app_env = Environment::fromString('dev', true);
        $this->assertTrue($app_env->isDebug());
        
        $app_env = Environment::fromString('staging');
        $this->assertFalse($app_env->isDebug());
        $app_env = Environment::fromString('staging', true);
        $this->assertTrue($app_env->isDebug());
        
        $app_env = Environment::fromString('testing');
        $this->assertFalse($app_env->isDebug());
        $app_env = Environment::fromString('testing', true);
        $this->assertTrue($app_env->isDebug());
    }
    
    /** @test */
    public function test_debug_is_enabled_by_default_when_using_named_dev_constructor()
    {
        $env = Environment::dev();
        $this->assertTrue($env->isDebug());
        
        $env = Environment::dev(false);
        $this->assertFalse($env->isDebug());
    }
    
    /** @test */
    public function test_debug_is_disabled_by_default_when_using_named_testing_constructor()
    {
        $env = Environment::testing();
        $this->assertFalse($env->isDebug());
        
        $env = Environment::testing(true);
        $this->assertTrue($env->isDebug());
    }
    
    /** @test */
    public function test_debug_is_disabled_by_default_when_using_named_staging_constructor()
    {
        $env = Environment::staging();
        $this->assertFalse($env->isDebug());
        
        $env = Environment::staging(true);
        $this->assertTrue($env->isDebug());
    }
    
    /** @test */
    public function test_debug_is_always_disabled_when_using_named_production_constructor()
    {
        $env = Environment::prod();
        $this->assertFalse($env->isDebug());
        
        $env = Environment::prod(true);
        $this->assertFalse($env->isDebug());
    }
    
    /** @test */
    public function test_is_running_in_console()
    {
        $env = Environment::prod();
        $this->assertTrue($env->isCli());
    }
    
}