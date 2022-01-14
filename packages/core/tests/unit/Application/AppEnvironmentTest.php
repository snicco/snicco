<?php

declare(strict_types=1);

namespace Tests\Core\unit\Application;

use InvalidArgumentException;
use Tests\Codeception\shared\UnitTest;
use Snicco\Core\Application\AppEnvironment;

final class AppEnvironmentTest extends UnitTest
{
    
    /** @test */
    public function test_from_string()
    {
        $app_env = AppEnvironment::fromString('prod');
        
        $this->assertInstanceOf(AppEnvironment::class, $app_env);
    }
    
    /** @test */
    public function test_exception_if_empty_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'App environment can not be constructed with an empty string.'
        );
        $app_env = AppEnvironment::fromString('');
    }
    
    /** @test */
    public function test_exception_if_not_a_valid_environment()
    {
        $app_env = AppEnvironment::fromString('prod');
        $app_env = AppEnvironment::fromString('testing');
        $app_env = AppEnvironment::fromString('dev');
        $app_env = AppEnvironment::fromString('staging');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'App environment has to be one of [testing,prod,dev,staging]. Got: [local]'
        );
        
        $app_env = AppEnvironment::fromString('local');
    }
    
    /** @test */
    public function test_as_string()
    {
        $app_env = AppEnvironment::fromString('prod');
        $this->assertSame('prod', $app_env->asString());
        
        $app_env = AppEnvironment::fromString('testing');
        $this->assertSame('testing', $app_env->asString());
        
        $app_env = AppEnvironment::fromString('dev');
        $this->assertSame('dev', $app_env->asString());
        
        $app_env = AppEnvironment::fromString('staging');
        $this->assertSame('staging', $app_env->asString());
    }
    
    /** @test */
    public function test_named_constructors()
    {
        $app_env = AppEnvironment::production();
        $this->assertSame('prod', $app_env->asString());
        
        $app_env = AppEnvironment::testing();
        $this->assertSame('testing', $app_env->asString());
        
        $app_env = AppEnvironment::dev();
        $this->assertSame('dev', $app_env->asString());
        
        $app_env = AppEnvironment::staging();
        $this->assertSame('staging', $app_env->asString());
    }
    
    /** @test */
    public function test_is_environment()
    {
        $env = AppEnvironment::production();
        $this->assertTrue($env->isProduction());
        $this->assertFalse($env->isDevelop());
        $this->assertFalse($env->isTesting());
        $this->assertFalse($env->isStaging());
        
        $env = AppEnvironment::dev();
        $this->assertTrue($env->isDevelop());
        $this->assertFalse($env->isProduction());
        $this->assertFalse($env->isTesting());
        $this->assertFalse($env->isStaging());
        
        $env = AppEnvironment::testing();
        $this->assertTrue($env->isTesting());
        $this->assertFalse($env->isProduction());
        $this->assertFalse($env->isDevelop());
        $this->assertFalse($env->isStaging());
        
        $env = AppEnvironment::staging();
        $this->assertTrue($env->isStaging());
        $this->assertFalse($env->isProduction());
        $this->assertFalse($env->isDevelop());
        $this->assertFalse($env->isTesting());
    }
    
    /** @test */
    public function test_debug_is_not_allowed_in_production()
    {
        $app_env = AppEnvironment::production();
        $this->assertFalse($app_env->isDebug());
        
        $app_env = AppEnvironment::fromString('prod');
        $this->assertFalse($app_env->isDebug());
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "App environment can not be in debug mode while in production."
        );
        $app_env = AppEnvironment::fromString('prod', true);
    }
    
    /** @test */
    public function test_debug_can_be_enabled_in_other_envs()
    {
        $app_env = AppEnvironment::fromString('dev');
        $this->assertFalse($app_env->isDebug());
        $app_env = AppEnvironment::fromString('dev', true);
        $this->assertTrue($app_env->isDebug());
        
        $app_env = AppEnvironment::fromString('staging');
        $this->assertFalse($app_env->isDebug());
        $app_env = AppEnvironment::fromString('staging', true);
        $this->assertTrue($app_env->isDebug());
        
        $app_env = AppEnvironment::fromString('testing');
        $this->assertFalse($app_env->isDebug());
        $app_env = AppEnvironment::fromString('testing', true);
        $this->assertTrue($app_env->isDebug());
    }
    
    /** @test */
    public function test_debug_is_enabled_by_default_when_using_named_dev_constructor()
    {
        $env = AppEnvironment::dev();
        $this->assertTrue($env->isDebug());
        
        $env = AppEnvironment::dev(false);
        $this->assertFalse($env->isDebug());
    }
    
    /** @test */
    public function test_debug_is_disabled_by_default_when_using_named_testing_constructor()
    {
        $env = AppEnvironment::testing();
        $this->assertFalse($env->isDebug());
        
        $env = AppEnvironment::testing(true);
        $this->assertTrue($env->isDebug());
    }
    
    /** @test */
    public function test_debug_is_disabled_by_default_when_using_named_staging_constructor()
    {
        $env = AppEnvironment::staging();
        $this->assertFalse($env->isDebug());
        
        $env = AppEnvironment::staging(true);
        $this->assertTrue($env->isDebug());
    }
    
    /** @test */
    public function test_debug_is_always_disabled_when_using_named_production_constructor()
    {
        $env = AppEnvironment::production();
        $this->assertFalse($env->isDebug());
        
        $env = AppEnvironment::production(true);
        $this->assertFalse($env->isDebug());
    }
    
    /** @test */
    public function test_is_running_in_console()
    {
        $env = AppEnvironment::production();
        $this->assertTrue($env->isCli());
    }
    
}