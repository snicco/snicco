<?php

declare(strict_types=1);


namespace Snicco\Bundle\BetterWPHooks\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Testing\BootsKernelForBundleTest;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\Kernel\Bootstrapper;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;

final class BetterWPHooksBundleTest extends TestCase
{
    use BootsKernelForBundleTest;

    private string $base_dir;
    private Directories $directories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = __DIR__ . '/fixtures/tmp';
        $this->directories = $this->setUpDirectories($this->base_dir);
    }

    protected function tearDown(): void
    {
        $this->tearDownDirectories($this->base_dir);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_runs_in_all_environments(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::testing());
        $this->assertTrue($kernel->usesBundle('sniccowp/better-wp-hooks-bundle'));

        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::prod());
        $this->assertTrue($kernel->usesBundle('sniccowp/better-wp-hooks-bundle'));

        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::dev());
        $this->assertTrue($kernel->usesBundle('sniccowp/better-wp-hooks-bundle'));

        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::staging());
        $this->assertTrue($kernel->usesBundle('sniccowp/better-wp-hooks-bundle'));
    }

    /**
     * @test
     */
    public function test_event_dispatcher_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories);

        $this->assertCanBeResolved(EventDispatcher::class, $kernel);
        $this->assertCanBeResolved(EventDispatcherInterface::class, $kernel);
    }

    /**
     * @test
     */
    public function test_listeners_are_resolved_from_the_container(): void
    {
        $kernel = $this->bootWithFixedConfig([
            'app' => [
                'bootstrappers' => [
                    ListenerBootstrapper::class
                ]
            ]
        ], $this->directories);

        $dispatcher = $kernel->container()->make(EventDispatcher::class);

        $std = new stdClass();
        $std->value = 'foo';

        /** @var stdClass $res */
        $res = $dispatcher->dispatch($std);

        $this->assertSame('foobar', $res->value);
    }

    /**
     * @test
     */
    public function test_event_mapper_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories);

        $this->assertCanBeResolved(EventMapper::class, $kernel);
    }

    /**
     * @test
     */
    public function in_testing_environment_the_testable_dispatcher_is_used(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::testing());

        $this->assertInstanceOf(
            TestableEventDispatcher::class,
            $d1 = $kernel->container()->make(EventDispatcher::class)
        );
        $this->assertInstanceOf(
            TestableEventDispatcher::class,
            $d2 = $kernel->container()->make(TestableEventDispatcher::class)
        );
        $this->assertSame($d1, $d2);

        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::prod());

        $this->assertInstanceOf(WPEventDispatcher::class, $kernel->container()->make(EventDispatcher::class));
    }

    protected function bundles(): array
    {
        return [
            Environment::ALL => [
                BetterWPHooksBundle::class
            ]
        ];
    }


}

class ListenerDependency
{

}

class Listener
{

    private ListenerDependency $dependency;

    public function __construct(ListenerDependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function __invoke(stdClass $std): void
    {
        $std->value = 'foobar';
    }

}

class ListenerBootstrapper implements Bootstrapper
{

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        //
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()->singleton(Listener::class, function () {
            return new Listener(new ListenerDependency());
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
        $dispatcher = $kernel->container()->make(EventDispatcher::class);
        $dispatcher->listen(stdClass::class, Listener::class);
    }
}