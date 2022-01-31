<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\TestCase;
use Snicco\Bridge\Blade\BladeStandalone;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewEngine;
use Symfony\Component\Finder\Finder;

use function preg_replace;
use function trim;
use function unlink;

class BladeTestCase extends TestCase
{

    protected string $blade_cache;
    protected string $blade_views;
    protected ViewEngine $view_engine;
    protected ViewComposerCollection $composers;
    protected GlobalViewContext $global_view_context;
    protected BladeStandalone $blade;

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

        $this->composers = new ViewComposerCollection(
            null,
            $global_view_context = new GlobalViewContext()
        );
        $blade = new BladeStandalone($this->blade_cache, [$this->blade_views], $this->composers);
        $blade->boostrap();
        $this->blade = $blade;
        $this->view_engine = new ViewEngine($blade->getBladeViewFactory());
        $this->global_view_context = $global_view_context;
        //$this->clearCache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        //$this->clearCache();
    }

    protected function assertViewContent(string $expected, $actual)
    {
        $actual = ($actual instanceof View) ? $actual->toString() : $actual;

        $actual = preg_replace("/\r|\n|\t|\s{2,}/", '', $actual);

        PHPUnit::assertSame($expected, trim($actual), 'View not rendered correctly.');
    }

    private function clearCache()
    {
        $files = Finder::create()->in([$this->blade_cache])->ignoreDotFiles(true);
        foreach ($files as $file) {
            unlink($file->getRealPath());
        }
    }

}