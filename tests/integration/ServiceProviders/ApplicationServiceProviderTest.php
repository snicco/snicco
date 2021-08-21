<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Snicco\Support\WP;
use Tests\stubs\TestApp;
use Snicco\Routing\Router;
use Snicco\Http\Redirector;
use Tests\FrameworkTestCase;
use Snicco\Support\WpFacade;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Application\Application;
use Snicco\Contracts\ViewInterface;
use Snicco\Http\Responses\RedirectResponse;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class ApplicationServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_wp_facade_has_the_correct_container()
    {
        $this->app->boot();
        $container = TestApp::container();
        $this->assertSame($container, WpFacade::getFacadeContainer());
    }
    
    /** @test */
    public function the_facade_can_be_swapped_during_test()
    {
        
        $this->app->boot();
        WP::shouldReceive('isAdmin')->andReturn(true);
        $this->assertTrue(WP::isAdmin());
        
    }
    
    /** @test */
    public function the_site_url_is_bound()
    {
        $this->app->boot();
        $this->assertSame(SITE_URL, TestApp::config('app.url'));
    }
    
    /** @test */
    public function the_relative_dist_path_is_set()
    {
        $this->app->boot();
        $this->assertSame('/dist', TestApp::config('app.dist'));
    }
    
    /** @test */
    public function the_dist_path_can_be_retrieved()
    {
        $this->app->boot();
        
        $expected = FIXTURES_DIR.DS.'dist';
        $this->assertSame($expected, TestApp::distPath());
        
        $expected = FIXTURES_DIR.DS.'dist'.DS.'foo';
        $this->assertSame($expected, TestApp::distPath('foo'));
        
    }
    
    /** @test */
    public function debug_mode_is_set()
    {
        $this->app->boot();
        
        $this->assertTrue($this->config->get('app.debug'));
    }
    
    /** @test */
    public function exception_handling_is_enabled_by_default()
    {
        $this->app->boot();
        
        $this->assertTrue($this->config->get('app.exception_handling'));
    }
    
    /** @test */
    public function the_package_root_is_bound()
    {
        $this->app->boot();
        
        $this->assertSame(ROOT_DIR, TestApp::config('app.package_root'));
    }
    
    /** @test */
    public function the_storage_dir_is_extended()
    {
        $this->app->boot();
        
        $this->assertSame(FIXTURES_DIR.DS.'storage', TestApp::config('app.storage_dir'));
    }
    
    /** @test */
    public function the_application_instance_can_be_aliased()
    {
        $this->app->boot();
        
        $this->assertInstanceOf(Application::class, TestApp::app());
        $this->assertSame($this->app, TestApp::app());
    }
    
    /** @test */
    public function the_router_can_be_aliased()
    {
        $this->app->boot();
        
        $this->assertInstanceOf(Router::class, TestApp::route());
    }
    
    /** @test */
    public function a_named_route_url_can_be_aliased()
    {
        $this->app->boot();
        $this->loadRoutes();
        $expected = '/alias/get';
        $this->assertSame($expected, TestApp::routeUrl('alias.get'));
    }
    
    /** @test */
    public function a_post_route_can_be_aliased()
    {
        $this->app->boot();
        
        $this->post('/alias/post')->assertSee('post');
    }
    
    /** @test */
    public function a_get_route_can_be_aliased()
    {
        $this->app->boot();
        
        $this->get('/alias/get')->assertSee('get');
    }
    
    /** @test */
    public function a_patch_route_can_be_aliased()
    {
        $this->app->boot();
        
        $this->patch('/alias/patch')->assertSee('patch');
    }
    
    /** @test */
    public function a_put_route_can_be_aliased()
    {
        $this->app->boot();
        
        $this->put('/alias/put')->assertSee('put');
    }
    
    /** @test */
    public function an_options_route_can_be_aliased()
    {
        $this->app->boot();
        
        $this->options('/alias/options')->assertSee('options');
    }
    
    /** @test */
    public function a_delete_route_can_be_aliased()
    {
        $this->app->boot();
        
        $this->delete('/alias/delete')->assertSee('delete');
    }
    
    /** @test */
    public function a_match_route_can_be_aliased()
    {
        $this->app->boot();
        
        $this->delete('/alias/match')->assertDelegatedToWordPress();
        $this->post('/alias/match')->assertOk()->assertSee('match');
    }
    
    /** @test */
    public function a_composer_can_be_added_as_an_alias()
    {
        $this->app->boot();
        
        TestApp::addComposer('foo', function () {
            // Assert no exception.
        });
        
        $this->assertTrue(true);
    }
    
    /** @test */
    public function a_view_can_be_created_as_an_alias()
    {
        $this->app->boot();
        
        $this->assertInstanceOf(ViewInterface::class, TestApp::view('view'));
    }
    
    /** @test */
    public function a_view_can_be_rendered_and_echoed()
    {
        $this->app->boot();
        
        ob_start();
        TestApp::render('view');
        
        $this->assertSame('Foobar', ob_get_clean());
    }
    
    /** @test */
    public function a_nested_view_can_be_included()
    {
        $this->app->boot();
        
        $view = TestApp::view('subview.php');
        
        $this->assertSame('Hello World', $view->toString());
    }
    
    /** @test */
    public function a_method_override_field_can_be_outputted()
    {
        $this->app->boot();
        
        $html = TestApp::methodField('PUT');
        
        $this->assertStringStartsWith('<input', $html);
        $this->assertStringContainsString('PUT', $html);
    }
    
    /** @test */
    public function the_url_generator_can_be_aliased()
    {
        $this->app->boot();
        
        $this->assertInstanceOf(UrlGenerator::class, TestApp::url());
    }
    
    /** @test */
    public function the_response_factory_can_be_aliased()
    {
        $this->app->boot();
        
        $this->assertInstanceOf(ResponseFactory::class, TestApp::response());
    }
    
    /** @test */
    public function a_redirect_response_can_be_created_as_an_alias()
    {
        $this->app->boot();
        
        $this->assertInstanceOf(RedirectResponse::class, TestApp::redirect('/foo'));
        $this->assertInstanceOf(Redirector::class, TestApp::redirect());
    }
    
    /** @test */
    public function an_exception_is_thrown_if_no_app_key_is_provided_in_the_config()
    {
        
        $this->withOutConfig('app.key');
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            "Your app.key config value is either missing or too insecure. Please generate a new one using Snicco\Application\Application::generateKey()"
        );
        $this->app->boot();
        
    }
    
}

