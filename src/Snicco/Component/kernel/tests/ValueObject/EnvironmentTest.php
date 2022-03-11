<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\ValueObject\Environment;

/**
 * @internal
 */
final class EnvironmentTest extends TestCase
{
    /**
     * @test
     */
    public function test_from_string(): void
    {
        $app_env = Environment::fromString('prod');

        $this->assertInstanceOf(Environment::class, $app_env);
    }

    /**
     * @test
     */
    public function test_exception_if_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('App environment can not be constructed with an empty string.');
        /** @psalm-suppress InvalidArgument */
        Environment::fromString('');
    }

    /**
     * @test
     */
    public function test_exception_if_not_a_valid_environment(): void
    {
        Environment::fromString('prod');
        Environment::fromString('testing');
        Environment::fromString('dev');
        Environment::fromString('staging');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('App environment has to be one of [testing,prod,dev,staging]. Got: [local]');

        /** @psalm-suppress InvalidArgument */
        Environment::fromString('local');
    }

    /**
     * @test
     */
    public function test_as_string(): void
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

    /**
     * @test
     */
    public function test_named_constructors(): void
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

    /**
     * @test
     */
    public function test_is_environment(): void
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

    /**
     * @test
     */
    public function test_debug_is_not_allowed_in_production(): void
    {
        $app_env = Environment::prod();
        $this->assertFalse($app_env->isDebug());

        $app_env = Environment::fromString('prod');
        $this->assertFalse($app_env->isDebug());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('App environment can not be in debug mode while in production.');
        Environment::fromString('prod', true);
    }

    /**
     * @test
     */
    public function test_debug_can_be_enabled_in_other_envs(): void
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

    /**
     * @test
     */
    public function test_debug_is_enabled_by_default_when_using_named_dev_constructor(): void
    {
        $env = Environment::dev();
        $this->assertTrue($env->isDebug());

        $env = Environment::dev(false);
        $this->assertFalse($env->isDebug());
    }

    /**
     * @test
     */
    public function test_debug_is_disabled_by_default_when_using_named_testing_constructor(): void
    {
        $env = Environment::testing();
        $this->assertFalse($env->isDebug());

        $env = Environment::testing(true);
        $this->assertTrue($env->isDebug());
    }

    /**
     * @test
     */
    public function test_debug_is_disabled_by_default_when_using_named_staging_constructor(): void
    {
        $env = Environment::staging();
        $this->assertFalse($env->isDebug());

        $env = Environment::staging(true);
        $this->assertTrue($env->isDebug());
    }

    /**
     * @test
     */
    public function test_debug_is_always_disabled_when_using_named_production_constructor(): void
    {
        $env = Environment::prod();
        $this->assertFalse($env->isDebug());
    }

    /**
     * @test
     */
    public function test_is_running_in_console(): void
    {
        $env = Environment::prod();
        $this->assertTrue($env->isCli());
    }
}
