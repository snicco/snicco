<?php

declare(strict_types=1);

namespace Snicco\Bundle\BetterWPHooks\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;

/**
 * @internal
 */
final class BetterWPHooksBundleTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_runs_in_all_environments(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wp-hooks-bundle'));

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wp-hooks-bundle'));

        $kernel = new Kernel($this->newContainer(), Environment::staging(), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wp-hooks-bundle'));

        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wp-hooks-bundle'));
    }

    /**
     * @test
     */
    public function test_event_dispatcher_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertCanBeResolved(EventDispatcher::class, $kernel);
        $this->assertCanBeResolved(EventDispatcherInterface::class, $kernel);
    }

    /**
     * @test
     */
    public function test_listeners_are_resolved_from_the_container(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterRegister(function (Kernel $kernel): void {
            $kernel->container()
                ->shared(Listener::class, fn (): Listener => new Listener(new ListenerDependency()));
            $dispatcher = $kernel->container()
                ->make(EventDispatcher::class);
            $dispatcher->listen(stdClass::class, Listener::class);
        });

        $kernel->boot();

        $dispatcher = $kernel->container()
            ->make(EventDispatcher::class);

        $std = new stdClass();
        $std->value = 'foo';

        $res = $dispatcher->dispatch($std);

        $this->assertSame('foobar', $res->value);
    }

    /**
     * @test
     */
    public function test_event_mapper_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertCanBeResolved(EventMapper::class, $kernel);
    }

    /**
     * @test
     */
    public function in_testing_environment_the_testable_dispatcher_is_used(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertInstanceOf(
            TestableEventDispatcher::class,
            $d1 = $kernel->container()
                ->make(EventDispatcher::class)
        );
        $this->assertInstanceOf(
            TestableEventDispatcher::class,
            $d2 = $kernel->container()
                ->make(TestableEventDispatcher::class)
        );
        $this->assertSame($d1, $d2);

        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);
        $kernel->boot();

        $this->assertInstanceOf(WPEventDispatcher::class, $kernel->container()->make(EventDispatcher::class));
    }

    /**
     * @test
     */
    public function exception_if_in_testing_no_testable_dispatcher_is_bound(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()
                ->instance(EventDispatcher::class, new BaseEventDispatcher());
        });

        $kernel->boot();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The testable event dispatcher did not get bound correctly. This should never happen.'
        );

        $kernel->container()
            ->make(TestableEventDispatcher::class);
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures';
    }
}

final class ListenerDependency
{
}

final class Listener
{
    public ListenerDependency $dependency;

    public function __construct(ListenerDependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function __invoke(stdClass $std): void
    {
        $std->value = 'foobar';
    }
}
