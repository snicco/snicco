<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bridge\Blade\BladeStandalone;
use Snicco\Component\Templating\Context\GlobalViewContext;
use Snicco\Component\Templating\Context\ViewContextResolver;
use Snicco\Component\Templating\TemplateEngine;
use Symfony\Component\Finder\Finder;

use function preg_replace;
use function trim;
use function unlink;

abstract class BladeTestCase extends TestCase
{
    protected string $blade_cache;

    protected string $blade_views;

    protected TemplateEngine $view_engine;

    protected ViewContextResolver $composers;

    protected GlobalViewContext $global_view_context;

    protected BladeStandalone $blade;

    /**
     * @psalm-suppress NullArgument
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Facade::class)) {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
        }

        if (class_exists(Container::class)) {
            Container::setInstance();
        }

        $this->blade_cache = __DIR__ . '/fixtures/cache';
        $this->blade_views = __DIR__ . '/fixtures/views';

        $this->composers = new ViewContextResolver($global_view_context = new GlobalViewContext(), null);
        $blade = new BladeStandalone($this->blade_cache, [$this->blade_views], $this->composers);
        $blade->bootstrap();
        $this->blade = $blade;

        $this->view_engine = new TemplateEngine($blade->getBladeViewFactory());
        $this->global_view_context = $global_view_context;
        $this->clearCache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearCache();
    }

    protected function assertViewContent(string $expected, string $actual): void
    {
        $actual = preg_replace('#
|
|	|\\s{2,}#', '', $actual);

        if (null === $actual) {
            throw new RuntimeException('preg_replace failed in test case assertion.');
        }

        PHPUnit::assertSame($expected, trim($actual), 'View not rendered correctly.');
    }

    private function clearCache(): void
    {
        $files = Finder::create()->in([$this->blade_cache])->ignoreDotFiles(true);
        foreach ($files as $file) {
            unlink($file->getRealPath());
        }
    }
}
