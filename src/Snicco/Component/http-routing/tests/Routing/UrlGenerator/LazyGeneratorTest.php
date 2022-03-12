<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlGenerator;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\Generator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\LazyGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

/**
 * @psalm-suppress DocblockTypeContradiction
 *
 * @internal
 */
final class LazyGeneratorTest extends TestCase
{
    use CreatesPsrRequests;
    use CreateTestPsr17Factories;

    private int $constructed;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constructed = 0;
    }

    /**
     * @test
     */
    public function test_to_works(): void
    {
        $lazy_generator = new LazyGenerator(function (): Generator {
            ++$this->constructed;

            return new Generator(
                new RouteCollection([]),
                new UrlGenerationContext('127.0.0.1'),
                WPAdminArea::fromDefaults(),
            );
        });

        $this->assertSame(0, $this->constructed);

        $this->assertSame('/foo?bar=baz', $lazy_generator->to('/foo', [
            'bar' => 'baz',
        ]));

        $this->assertSame(1, $this->constructed);

        $this->assertSame(
            'https://127.0.0.1/foo?bar=baz',
            $lazy_generator->to('/foo', [
                'bar' => 'baz',
            ], UrlGenerator::ABSOLUTE_URL)
        );

        $this->assertSame(1, $this->constructed);
    }

    /**
     * @test
     */
    public function test_to_route_works(): void
    {
        $lazy_generator = new LazyGenerator(function (): Generator {
            ++$this->constructed;

            $route = Route::create('/foo', Route::DELEGATE, 'foo');

            return new Generator(
                new RouteCollection([$route]),
                new UrlGenerationContext('127.0.0.1'),
                WPAdminArea::fromDefaults(),
            );
        });

        $this->assertSame(0, $this->constructed);

        $this->assertSame('/foo', $lazy_generator->toRoute('foo'));

        $this->assertSame(1, $this->constructed);

        $this->expectException(RouteNotFound::class);
        $lazy_generator->toRoute('bar');
    }

    /**
     * @test
     */
    public function test_to_login_works(): void
    {
        $lazy_generator = new LazyGenerator(function (): Generator {
            ++$this->constructed;

            return new Generator(
                new RouteCollection(),
                new UrlGenerationContext('127.0.0.1'),
                WPAdminArea::fromDefaults(),
            );
        });

        $this->assertSame(0, $this->constructed);

        $this->assertSame('/wp-login.php?bar=baz', $lazy_generator->toLogin([
            'bar' => 'baz',
        ]));

        $this->assertSame(1, $this->constructed);
    }
}
