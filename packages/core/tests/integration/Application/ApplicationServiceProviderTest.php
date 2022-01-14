<?php

declare(strict_types=1);

namespace Tests\Core\integration\Application;

use Snicco\Core\Support\WP;
use Snicco\Core\Routing\Router;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Application\Application;
use Snicco\Core\Http\StatelessRedirector;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Http\DefaultResponseFactory;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Core\Http\Responses\RedirectResponse;
use Snicco\Core\Routing\UrlGenerator\InternalUrlGenerator;
use Snicco\Core\ExceptionHandling\Exceptions\ConfigurationException;

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
        $this->assertSame('/alias/get', TestApp::routeUrl('alias.get'));
    }
    
    /** @test */
    public function a_post_route_can_be_aliased()
    {
        $this->bootApp();
        
        $response = $this->post('/alias/post');
        $response->assertSee('post');
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
        
        $this->delete('/alias/match')->assertDelegated();
        $this->post('/alias/match')->assertOk()->assertSee('match');
    }
    
    /** @test */
    public function the_url_generator_can_be_aliased()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(InternalUrlGenerator::class, TestApp::url());
    }
    
    /** @test */
    public function the_response_factory_can_be_aliased()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(ResponseFactory::class, TestApp::response());
        $this->assertInstanceOf(DefaultResponseFactory::class, TestApp::response());
    }
    
    /** @test */
    public function a_redirect_response_can_be_created_as_an_alias()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(RedirectResponse::class, TestApp::redirect('/foo'));
        $this->assertInstanceOf(Redirector::class, TestApp::redirect());
    }
    
    /** @test */
    public function an_exception_is_thrown_if_no_app_key_is_provided_in_the_config()
    {
        $provider = $this->customizeConfigProvider();
        $provider->remove('app.key');
        $this->withAddedProvider($provider);
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            "Your app.key config value is either missing or too insecure. Please generate a new one using Snicco\Core\Application\Application::generateKey()"
        );
        $this->bootApp();
    }
    
}

