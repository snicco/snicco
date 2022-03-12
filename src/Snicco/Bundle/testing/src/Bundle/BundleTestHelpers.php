<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Bundle;

use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;

trait BundleTestHelpers
{
    protected BundleTest $bundle_test;

    protected Directories $directories;

    /**
     * @var string[]
     */
    private array $fixture_config_files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
    }

    protected function tearDown(): void
    {
        $this->bundle_test->tearDownDirectories();
        parent::tearDown();
    }

    abstract protected function fixturesDir(): string;

    protected function newContainer(): DIContainer
    {
        return $this->bundle_test->newContainer();
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     */
    final protected function assertCanBeResolved(string $class, Kernel $kernel): void
    {
        try {
            /** @var T $resolved */
            $resolved = $kernel->container()
                ->get($class);
        } catch (ContainerExceptionInterface $e) {
            PHPUnit::fail("Class [{$class}] could not be resolved.\nMessage: " . $e->getMessage());
        }

        PHPUnit::assertInstanceOf($class, $resolved);
    }

    final protected function assertNotBound(string $identifier, Kernel $kernel): void
    {
        try {
            $kernel->container()
                ->get($identifier);
            PHPUnit::fail(sprintf('Identifier [%s] was bound in the container.', $identifier));
        } catch (NotFoundExceptionInterface $e) {
            PHPUnit::assertStringContainsString($identifier, $e->getMessage());
        }
    }
}
