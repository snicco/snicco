<?php

declare(strict_types=1);

namespace Tests\Core\integration\Routing;

use Snicco\Support\WP;
use Snicco\Support\Arr;
use Snicco\Contracts\ServiceProvider;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\EventDispatcher\Events\IncomingAjaxRequest;
use Snicco\EventDispatcher\Events\IncomingAdminRequest;

class RouteRegistrationTest extends FrameworkTestCase
{
    
    /** @test */
    public function web_routes_are_loaded_in_the_main_wp_function()
    {
        $this->withRequest($this->frontendRequest('GET', '/foo'));
        $this->bootApp();
        
        global $wp;
        $wp->main();
        
        $this->sentResponse()->assertSee('foo')->assertOk();
    }
    
    /** @test */
    public function admin_routes_are_run_on_the_loaded_on_admin_init_hook()
    {
        $this->withoutHooks();
        $this->withRequest($this->adminRequest('GET', 'foo'));
        $this->bootApp();
        
        do_action('admin_init');
        $hook = WP::pluginPageHook();
        do_action("load-$hook");
        
        do_action('all_admin_notices');
        
        $this->sentResponse()->assertOk()->assertSee('FOO_ADMIN');
    }
    
    /** @test */
    public function ajax_routes_are_loaded_first_on_admin_init()
    {
        $this->withoutHooks();
        $this->withRequest($this->adminAjaxRequest('POST', 'foo_action'));
        $this->bootApp();
        
        do_action('admin_init');
        
        $this->sentResponse()->assertOk()->assertSee('FOO_AJAX_ACTION');
    }
    
    /** @test */
    public function admin_routes_are_also_run_for_other_admin_pages_besides_admin_php()
    {
        $this->withRequest($this->adminRequest('GET', '', 'profile.php'));
        
        $this->withoutHooks()->bootApp();
        
        do_action('admin_init');
        global $pagenow;
        do_action("load-$pagenow");
        
        $this->sentResponse()->assertRedirect('/foo');
    }
    
    /** @test */
    public function ajax_routes_are_only_run_if_the_request_has_an_action_parameter()
    {
        $request = $this->adminAjaxRequest('POST', 'foo_action')->withParsedBody([]);
        $this->withRequest($request);
        $this->withoutHooks()->bootApp();
        
        do_action('admin_init');
        
        $this->assertNoResponse();
    }
    
    /** @test */
    public function the_fallback_route_controller_is_registered_for_web_routes()
    {
        $this->withRequest($this->frontendRequest('GET', '/post1'));
        $this->makeFallbackConditionPass();
        $this->bootApp();
        
        global $wp;
        $wp->main();
        
        $this->sentResponse()->assertSee('get_condition')->assertOk();
    }
    
    /** @test */
    public function the_fallback_controller_does_not_match_admin_routes()
    {
        $this->withoutHooks();
        $this->withRequest($request = $this->adminRequest('GET', 'bogus'))->bootApp();
        
        $this->makeFallbackConditionPass();
        
        $this->dispatcher->dispatch(new IncomingAdminRequest($request));
        
        $this->sentResponse()->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_fallback_controller_does_not_match_ajax_routes()
    {
        $this->withRequest($request = $this->adminAjaxRequest('POST', 'bogus'));
        $this->bootApp();
        $this->makeFallbackConditionPass();
        
        $this->dispatcher->dispatch(new IncomingAjaxRequest($request));
        
        $this->sentResponse()->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function named_groups_prefixes_are_applied_for_admin_routes()
    {
        $this->withoutHooks();
        $this->withRequest($this->adminRequest('GET', 'foo'));
        $this->bootApp();
        
        $this->assertSame('/wp-admin/admin.php?page=foo', TestApp::routeUrl('admin.foo'));
    }
    
    /** @test */
    public function named_groups_are_applied_for_ajax_routes()
    {
        $this->bootApp();
        
        $this->assertSame('/wp-admin/admin-ajax.php', TestApp::routeUrl('ajax.foo'));
    }
    
    /** @test */
    public function custom_routes_dirs_can_be_provided()
    {
        $request = $this->frontendRequest('GET', '/other');
        $this->withRequest($request);
        $this->withAddedProvider(RoutingDefinitionServiceProvider::class)->withoutHooks()
             ->bootApp();
        
        global $wp;
        $wp->main();
        
        $this->sentResponse()->assertOk()->assertSee('other');
    }
    
    /** @test */
    public function a_file_with_the_same_name_will_not_be_loaded_twice_for_standard_routes()
    {
        $request = $this->frontendRequest('GET', '/web-other');
        
        $this->withRequest($request);
        $this->withAddedProvider(RoutingDefinitionServiceProvider::class)->withoutHooks()
             ->bootApp();
        
        global $wp;
        $wp->main();
        
        // without the filtering of file names the route in /OtherRoutes/web.php would match
        $this->sentResponse()->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function route_files_starting_with_an_underscore_will_not_be_required_automatically()
    {
        $this->withRequest($this->frontendRequest('GET', '/underscore-route'));
        $this->bootApp();
        
        global $wp;
        $wp->main();
        
        $this->sentResponse()->assertDelegatedToWordPress();
    }
    
    protected function makeFallbackConditionPass()
    {
        $GLOBALS['test']['pass_fallback_route_condition'] = true;
    }
    
}

class RoutingDefinitionServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $routes = Arr::wrap($this->config->get('routing.definitions'));
        
        $routes = array_merge($routes, [TEST_APP_BASE_PATH.DS.'other-routes']);
        
        $this->config->set('routing.definitions', $routes);
    }
    
    function bootstrap() :void
    {
    }
    
}

