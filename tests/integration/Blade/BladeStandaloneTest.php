<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Snicco\View\ViewEngine;
use PHPUnit\Framework\Assert;
use Snicco\Blade\BladeStandalone;
use Codeception\TestCase\WPTestCase;
use Snicco\View\ViewComposerCollection;
use Snicco\View\Contracts\ViewInterface;

final class BladeStandaloneTest extends WPTestCase
{
    
    /**
     * @var ViewEngine
     */
    private $view_engine;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $blade = new BladeStandalone(
            __DIR__.'/cache',
            [__DIR__.'/views'],
            new ViewComposerCollection()
        );
        $blade->boostrap();
        
        $this->view_engine = new ViewEngine($blade->getBladeViewFactory());
    }
    
    /** @test */
    public function nested_views_can_be_rendered_relative_to_the_views_directory()
    {
        $view = $this->view_engine->make('nested.view');
        
        $this->assertViewContent('FOO', $view);
    }
    
    /** @test */
    public function the_first_available_view_can_be_created()
    {
        $first = $this->view_engine->make(['bogus1', 'bogus2', 'foo']);
        
        $this->assertViewContent('FOO', $first);
    }
    
    protected function assertViewContent(string $expected, $actual)
    {
        $actual = ($actual instanceof ViewInterface) ? $actual->toString() : $actual;
        
        $actual = preg_replace("/\r|\n|\t|\s{2,}/", '', $actual);
        
        Assert::assertSame($expected, trim($actual), 'View not rendered correctly.');
    }
    
}