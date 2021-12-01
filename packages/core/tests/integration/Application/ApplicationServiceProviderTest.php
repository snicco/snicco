<?php

declare(strict_types=1);

namespace Tests\Core\integration\Application;

use Snicco\Support\WP;
use Snicco\Routing\Router;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Application\Application;
use Snicco\Http\StatelessRedirector;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Http\Responses\RedirectResponse;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

use const DS;
use const SITE_URL;

class ApplicationServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_wp_api_is_not_a_mockery_instance()
    {
        $this->bootApp();
        $this->assertFalse(WP::isUserLoggedIn());
    }
    
    /** @test */
    public function the_facade_can_be_swapped_during_test()
    {
        $this->bootApp();
        WP::shouldReceive('isUserLoggedIn')->andReturn(true);
        $this->assertTrue(WP::isUserLoggedIn());
    }
    
    /** @test */
    public function the_site_url_is_bound()
    {
        $this->bootApp();
        $this->assertSame(SITE_URL, TestApp::config('app.url'));
    }
    
    /** @test */
    public function the_relative_dist_path_is_set()
    {
        $this->bootApp();
        $this->assertSame('/dist', TestApp::config('app.dist'));
    }
    
    /** @test */
    public function the_dist_path_can_be_retrieved()
    {
        $this->bootApp();
        
        $expected = TEST_APP_BASE_PATH.DS.'dist';
        $this->assertSame($expected, TestApp::distPath());
        
        $expected = TEST_APP_BASE_PATH.DS.'dist'.DS.'foo';
        $this->assertSame($expected, TestApp::distPath('foo'));
    }
    
    /** @test */
    public function debug_mode_is_set()
    {
        $this->withAddedConfig('app.debug', true);
        $this->bootApp();
        $this->assertTrue($this->config->get('app.debug'));
    }
    
    /** @test */
    public function exception_handling_is_enabled_by_default()
    {
        $this->bootApp();
        
        $this->assertTrue($this->config->get('app.exception_handling'));
    }
    
    /** @test */
    public function the_package_root_is_bound()
    {
        $this->bootApp();
        
        $this->assertSame(PACKAGES_DIR.DS.'core', TestApp::config('app.package_root'));
    }
    
    /** @test */
    public function the_storage_dir_is_extended()
    {
        $this->bootApp();
        
        $this->assertSame(TEST_APP_BASE_PATH.DS.'storage', TestApp::config('app.storage_dir'));
    }
    
    /** @test */
    public function the_base_path_is_set()
    {
        $this->bootApp();
        
        $this->assertSame(TEST_APP_BASE_PATH, TestApp::config('app.base_path'));
    }
    
    /** @test */
    public function the_application_instance_can_be_aliased()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(Application::class, TestApp::app());
        $this->assertSame($this->app, TestApp::app());
    }
    
    /** @test */
    public function the_router_can_be_aliased()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(Router::class, TestApp::route());
    }
    
    /** @test */
    public function a_named_route_url_can_be_aliased()
    {
        $this->bootApp();
        $expected = '/alias/get';
        $this->assertSame($expected, TestApp::routeUrl('alias.get'));
    }
    
    /** @test */
    public function a_post_route_can_be_aliased()
    {
        $this->bootApp();
        
        $this->post('/alias/post')->assertSee('post');
    }
    
    /** @test */
    public function a_get_route_can_be_aliased()
    {
        $this->bootApp();
        
        $this->get('/alias/get')->assertSee('get');
    }
    
    /** @test */
    public function a_patch_route_can_be_aliased()
    {
        $this->bootApp();
        
        $this->patch('/alias/patch')->assertSee('patch');
    }
    
    /** @test */
    public function a_put_route_can_be_aliased()
    {
        $this->bootApp();
        
        $this->put('/alias/put')->assertSee('put');
    }
    
    /** @test */
    public function an_options_route_can_be_aliased()
    {
        $this->bootApp();
        
        $this->options('/alias/options')->assertSee('options');
    }
    
    /** @test */
    public function a_delete_route_can_be_aliased()
    {
        $this->bootApp();
        
        $this->delete('/alias/delete')->assertSee('delete');
    }
    
    /** @test */
    public function a_match_route_can_be_aliased()
    {
        $this->bootApp();
        
        $this->delete('/alias/match')->assertDelegatedToWordPress();
        $this->post('/alias/match')->assertOk()->assertSee('match');
    }
    
    /** @test */
    public function a_composer_can_be_added_as_an_alias()
    {
        $this->bootApp();
        
        TestApp::addComposer('foo', function () {
            // Assert no exception.
        });
        
        $this->assertTrue(true);
    }
    
    /** @test */
    public function a_view_can_be_created_as_an_alias()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(ViewInterface::class, TestApp::view('view'));
    }
    
    /** @test */
    public function a_view_can_be_rendered_and_echoed()
    {
        $this->bootApp();
        
        ob_start();
        TestApp::render('view');
        
        $this->assertSame('Foobar', ob_get_clean());
    }
    
    /** @test */
    public function a_nested_view_can_be_included()
    {
        $this->bootApp();
        
        $view = TestApp::view('subdirectory.subview.php');
        
        $this->assertSame('Hello World', $view->toString());
    }
    
    /** @test */
    public function a_method_override_field_can_be_outputted()
    {
        $this->bootApp();
        
        $html = TestApp::methodField('PUT');
        
        $this->assertStringStartsWith('<input', $html);
        $this->assertStringContainsString('PUT', $html);
    }
    
    /** @test */
    public function the_url_generator_can_be_aliased()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(UrlGenerator::class, TestApp::url());
    }
    
    /** @test */
    public function the_response_factory_can_be_aliased()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(ResponseFactory::class, TestApp::response());
    }
    
    /** @test */
    public function a_redirect_response_can_be_created_as_an_alias()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(RedirectResponse::class, TestApp::redirect('/foo'));
        $this->assertInstanceOf(StatelessRedirector::class, TestApp::redirect());
    }
    
    /** @test */
    public function an_exception_is_thrown_if_no_app_key_is_provided_in_the_config()
    {
        $provider = $this->customizeConfigProvider();
        $provider->remove('app.key');
        $this->withAddedProvider($provider);
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            "Your app.key config value is either missing or too insecure. Please generate a new one using Snicco\Application\Application::generateKey()"
        );
        $this->bootApp();
    }
    
    /** @test */
    public function the_request_can_be_resolved_as_an_alias()
    {
        $this->withRequest(TestRequest::from('GET', '/foo'));
        $this->bootApp();
        
        $this->assertInstanceOf(Request::class, $request = TestApp::request());
        
        $this->assertSame('/foo', $request->path());
    }
    
}

