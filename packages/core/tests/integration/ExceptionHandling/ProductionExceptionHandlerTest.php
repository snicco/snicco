<?php

declare(strict_types=1);

namespace Tests\Core\integration\ExceptionHandling;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Tests\Core\fixtures\TestDoubles\TestLogger;
use Tests\Codeception\shared\FrameworkTestCase;

class ProductionExceptionHandlerTest extends FrameworkTestCase
{
    
    /** @test */
    public function a_http_exception_are_rendered_into_a_generic_error_page()
    {
        $response = $this->get('/error/500');
        $response->assertSee('VIEW:error.php,STATUS:500,MESSAGE:Something went wrong.');
        $response->assertStatus(500);
        $response->assertIsHtml();
    }
    
    /** @test */
    public function an_exception_in_the_routing_flow_is_logged()
    {
        $this->bootApp();
        $this->instance(LoggerInterface::class, $logger = new TestLogger());
        
        $this->get('/error/500')->assertStatus(500);
        $logger->assertHasLogLevelEntry(LogLevel::ERROR, 'Secret logging stuff.');
    }
    
    /** @test */
    public function http_exceptions_are_transformed_into_specific_status_code_views_if_they_exists()
    {
        $response = $this->get('/error/419');
        $response->assertSee('VIEW:419.php,STATUS:419,MESSAGE:The link you followed expired.');
        $response->assertStatus(419);
        $response->assertIsHtml();
    }
    
    /** @test */
    public function a_http_exception_which_cant_be_mapped_with_a_status_code_gets_rendered_with_the_default_error_view()
    {
        $this->get('/error/400')
             ->assertStatus(400)
             ->assertSee('VIEW:error.php,STATUS:400,MESSAGE:Something went wrong.')
             ->assertIsHtml();
    }
    
    /** @test */
    public function an_admin_request_can_have_specific_admin_error_views_that_have_priority_over_non_admin_views()
    {
        $response = $this->getAdminPage('error');
        $response->assertSee('VIEW:419-admin.php,STATUS:419,MESSAGE:The link you followed expired.')
                 ->assertStatus(419)
                 ->assertIsHtml();
    }
    
    /** @test */
    public function request_that_expect_json_are_rendered_as_json()
    {
        $this->getJson('/error/500')
             ->assertSee(json_encode('Something went wrong.'), false)
             ->assertStatus(500)->assertIsJson();
    }
    
    /** @test */
    public function errors_that_can_not_be_converted_to_any_at_all_are_rendered_with_the_default_error_view()
    {
        $response = $this->get('/error/fatal');
        $response->assertSee('VIEW:error.php,STATUS:500,MESSAGE:Something went wrong.');
        $response->assertStatus(500);
        $response->assertIsHtml();
    }
    
}