<?php

declare(strict_types=1);

namespace Tests\BladeBundle\integration;

use Illuminate\View\Factory;
use Snicco\Http\ResponseFactory;
use Snicco\Blade\BladeViewFactory;
use Illuminate\Support\MessageBag;
use Illuminate\View\FileViewFinder;
use Illuminate\Container\Container;
use Illuminate\Support\ViewErrorBag;
use Snicco\View\Contracts\ViewFactory;
use Snicco\Session\SessionServiceProvider;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;
use Snicco\BladeBundle\BladeServiceProvider;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;

class BladeServiceProviderTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->withAddedConfig('view.paths', [dirname(__DIR__).'/fixtures/views']);
        });
        parent::setUp();
    }
    
    public function the_blade_view_factory_is_bound_correctly()
    {
        $this->bootApp();
        $container = Container::getInstance();
        $this->assertInstanceOf(Factory::class, $container->make('view'));
    }
    
    /** @test */
    public function the_blade_view_finder_is_bound_correctly()
    {
        $this->bootApp();
        $container = Container::getInstance();
        $this->assertInstanceOf(FileViewFinder::class, $container->make('view.finder'));
    }
    
    /** @test */
    public function the_blade_compiler_is_bound_correctly()
    {
        $this->bootApp();
        $container = Container::getInstance();
        $this->assertInstanceOf(BladeCompiler::class, $container->make('blade.compiler'));
    }
    
    /** @test */
    public function the_engine_resolver_is_bound_correctly()
    {
        $this->bootApp();
        $container = Container::getInstance();
        $this->assertInstanceOf(EngineResolver::class, $container->make('view.engine.resolver'));
    }
    
    /** @test */
    public function the_view_service_now_uses_the_blade_engine()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            BladeViewFactory::class,
            $this->app->resolve(ViewFactory::class)
        );
    }
    
    /** @test */
    public function a_custom_view_cache_path_can_be_provided()
    {
        $this->withAddedConfig('view.blade_cache', __DIR__)->bootApp();
        
        $this->assertSame(__DIR__, Container::getInstance()['config']['view.compiled']);
    }
    
    /** @test */
    public function a_blade_view_can_be_transformed()
    {
        $this->bootApp();
        $view = TestApp::view('blade-view');
        
        /** @var ResponseFactory $response_factory */
        $response_factory = $this->app->resolve(ResponseFactory::class);
        
        $this->assertViewContent(
            'FOO',
            $response_factory->toResponse($view)->getBody()->__toString()
        );
    }
    
    /**
     * @test
     */
    public function custom_csrf_directives_work_when_sessions_are_enabled()
    {
        $this->withAddedConfig('session.enabled', true)->bootApp();
        TestApp::session()->start();
        $view = TestApp::view('csrf');
        $content = $view->toString();
        
        $this->assertStringContainsString('_token', $content);
        $this->assertStringContainsString(TestApp::session()->csrfToken(), $content);
        $this->assertStringStartsWith('<input', $content);
    }
    
    /**
     * @test
     */
    public function method_directive_works()
    {
        $this->bootApp();
        $view = TestApp::view('method');
        $content = $view->toString();
        $this->assertStringContainsString("<input type='hidden' name='_method", $content);
        $this->assertStringContainsString("value='PUT|", $content);
    }
    
    /**
     * @test
     */
    public function error_directive_works()
    {
        $this->bootApp();
        
        $error_bag = new ViewErrorBag();
        $default = new MessageBag();
        $default->add('title', 'ERROR_WITH_YOUR_TITLE');
        $error_bag->put('default', $default);
        $view = TestApp::view('error');
        $view->with('errors', $error_bag);
        
        $this->assertViewContent('ERROR_WITH_YOUR_TITLE', $view);
        
        $view = TestApp::view('error');
        $error_bag = new ViewErrorBag();
        $default = new MessageBag();
        $error_bag->put('default', $default);
        $view->with('errors', $error_bag);
        
        $this->assertViewContent('NO ERRORS WITH YOUR VIEW', $view);
    }
    
    /**
     * @test
     */
    public function errors_work_with_custom_error_bags()
    {
        $this->bootApp();
        
        // Named error bag.
        $error_bag = new ViewErrorBag();
        $custom = new MessageBag();
        $custom->add('title', 'CUSTOM_BAG_ERROR');
        $error_bag->put('custom', $custom);
        $view = TestApp::view('error-custom-bag');
        $view->with('errors', $error_bag);
        
        $this->assertViewContent('CUSTOM_BAG_ERROR', $view);
        
        // Wrong Named error bag.
        $error_bag = new ViewErrorBag();
        $bogus = new MessageBag();
        $bogus->add('title', 'CUSTOM_BAG_ERROR');
        $error_bag->put('bogus', $bogus);
        $view = TestApp::view('error-custom-bag');
        $view->with('errors', $error_bag);
        
        $this->assertViewContent('NO ERRORS IN CUSTOM BAG', $view);
    }
    
    protected function packageProviders() :array
    {
        return [
            BladeServiceProvider::class,
            SessionServiceProvider::class,
        ];
    }
    
}