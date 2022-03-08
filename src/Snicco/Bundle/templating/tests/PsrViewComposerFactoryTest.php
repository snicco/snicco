<?php

declare(strict_types=1);


namespace Snicco\Bundle\Templating\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Templating\PsrViewComposerFactory;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use stdClass;

final class PsrViewComposerFactoryTest extends TestCase
{

    /**
     * @test
     */
    public function composers_are_resolved_from_the_container_if_bound(): void
    {
        $bundle_test = new BundleTest(__DIR__ . '/fixtures');
        $container = $bundle_test->newContainer();

        $factory = new PsrViewComposerFactory($container);

        $composer = new TestComposerNewable();
        $container->instance(TestComposerNewable::class, $composer);

        $this->assertSame($composer, $factory->create(TestComposerNewable::class));
    }

    /**
     * @test
     */
    public function composers_are_newed_up_if_not_bound(): void
    {
        $bundle_test = new BundleTest(__DIR__ . '/fixtures');
        $container = $bundle_test->newContainer();

        $factory = new PsrViewComposerFactory($container);

        $this->assertInstanceOf(TestComposerNewable::class, $factory->create(TestComposerNewable::class));
    }

    /**
     * @test
     */
    public function test_exception_if_not_bound_and_not_newable(): void
    {
        $bundle_test = new BundleTest(__DIR__ . '/fixtures');
        $container = $bundle_test->newContainer();

        $factory = new PsrViewComposerFactory($container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(TestComposerNotNewable::class);
        $factory->create(TestComposerNotNewable::class);
    }

}

class TestComposerNewable implements ViewComposer
{
    public function compose(View $view): View
    {
        return $view;
    }
}

class TestComposerNotNewable implements ViewComposer
{
    private stdClass $std;

    public function __construct(stdClass $std)
    {
        $this->std = $std;
    }

    public function compose(View $view): View
    {
        return $view;
    }
}