<?php

declare(strict_types=1);

namespace Tests\Blade;

use Snicco\Support\WP;
use Snicco\View\ViewEngine;
use Snicco\Blade\BladeStandalone;
use Snicco\View\GlobalViewContext;
use Illuminate\Container\Container;
use Codeception\TestCase\WPTestCase;
use Illuminate\Support\Facades\Facade;
use Snicco\View\ViewComposerCollection;
use Tests\Codeception\shared\helpers\AssertViewContent;

class BladeTestCase extends WPTestCase
{
    
    use AssertViewContent;
    
    /**
     * @var string
     */
    protected $blade_cache;
    
    /**
     * @var string
     */
    protected $blade_views;
    
    /**
     * @var ViewEngine
     */
    protected $view_engine;
    
    /**
     * @var ViewComposerCollection
     */
    protected $composers;
    
    /**
     * @var GlobalViewContext
     */
    protected $global_view_context;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->rmdir(__DIR__.'fixtures/cache');
        
        if (class_exists(Facade::class)) {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
        }
        
        if (class_exists(Container::class)) {
            Container::setInstance();
        }
        
        WP::reset();
        
        $this->blade_cache = __DIR__.'/fixtures/cache';
        $this->blade_views = __DIR__.'/fixtures/views';
        
        $this->composers =
            new ViewComposerCollection(null, $global_view_context = new GlobalViewContext());
        $blade = new BladeStandalone($this->blade_cache, [$this->blade_views], $this->composers);
        $blade->boostrap();
        $this->view_engine = new ViewEngine($blade->getBladeViewFactory());
        $this->global_view_context = $global_view_context;
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        $this->rmdir(__DIR__.'fixtures/cache');
    }
    
}