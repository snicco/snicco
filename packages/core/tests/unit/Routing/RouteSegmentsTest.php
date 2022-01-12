<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Snicco\Core\Routing\Condition\QueryStringCondition;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

class RouteSegmentsTest extends RoutingTestCase
{
    
    /** @test */
    public function url_encoded_routes_can_be_matched_by_their_decoded_path()
    {
        $this->routeConfigurator()->get(
            'city',
            '/german-city/{city}',
            [RoutingTestController::class, 'dynamic']
        );
        
        $request = $this->frontendRequest('GET', "/german-city/münchen");
        $this->assertResponseBody('dynamic:münchen', $request);
    }
    
    /** @test */
    public function non_ascii_routes_can_be_matched()
    {
        // новости = russian for news
        $this->routeConfigurator()->get('r1', 'новости', [RoutingTestController::class, 'static']);
        
        $this->routeConfigurator()->get(
            'r2',
            '/foo/{bar}',
            [RoutingTestController::class, 'dynamic']
        );
        
        $request = $this->frontendRequest('GET', rawurlencode('новости'));
        $this->assertResponseBody('static', $request);
        
        $request = $this->frontendRequest('GET', '/foo/'.rawurlencode('новости'));
        $this->assertResponseBody('dynamic:новости', $request);
    }
    
    /** @test */
    public function routes_are_case_sensitive()
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        
        $this->assertResponseBody('static', $this->frontendRequest('GET', '/foo'));
        $this->assertResponseBody('', $this->frontendRequest('GET', '/FOO'));
    }
    
    /** @test */
    public function url_encoded_query_string_conditions_work()
    {
        $this->routeConfigurator()->get('r1', '/foo', [RoutingTestController::class, 'dynamic'])
             ->condition(QueryStringCondition::class, ['page' => 'bayern münchen']
             );
        
        $request = $this->frontendRequest('GET', "/foo?page=bayern münchen");
        $this->assertResponseBody('dynamic:bayern münchen', $request);
    }
    
    /** @test */
    public function route_segments_can_contain_encoded_forward_slashes()
    {
        $this->routeConfigurator()->get(
            'bands',
            '/bands/{band}/{song?}',
            [RoutingTestController::class, 'bandSong']
        );
        
        $request = $this->frontendRequest('GET', 'https://music.com/bands/AC%2FDC/foo_song');
        $this->assertResponseBody(
            'Show song [foo_song] of band [AC/DC].',
            $request
        );
        
        $request = $this->frontendRequest('GET', 'https://music.com/bands/AC%2FDC');
        $this->assertResponseBody(
            'Show all songs of band [AC/DC].',
            $request
        );
    }
    
    /** @test */
    public function urldecoded_route_definitions_can_match_url_encoded_paths()
    {
        $this->routeConfigurator()->get('r1', 'münchen', RoutingTestController::class);
        
        $request = $this->frontendRequest('GET', 'münchen');
        
        $this->assertResponseBody('static', $request);
    }
    
    /** @test */
    public function routes_can_contain_a_plus_sign()
    {
        $this->routeConfigurator()->get('r1', 'foo+bar', RoutingTestController::class);
        
        $request = $this->frontendRequest('GET', '/foo+bar');
        $this->assertResponseBody('static', $request);
    }
    
    /** @test */
    public function regex_can_be_added_as_a_condition_as_array_syntax()
    {
        $this->routeConfigurator()->get(
            'users',
            'users/{user}',
            [RoutingTestController::class, 'dynamic']
        )
             ->requirements(['user' => '[a]+']);
        
        $request = $this->frontendRequest('GET', '/users/a');
        $this->assertResponseBody('dynamic:a', $request);
        
        $request = $this->frontendRequest('GET', '/users/b');
        $this->assertEmptyBody($request);
    }
    
    /** @test */
    public function multiple_regex_conditions_can_be_added()
    {
        $this->routeConfigurator()->get(
            'r1',
            '/user/{id}/{name}',
            [RoutingTestController::class, 'twoParams']
        )->requirements(['id' => '[a]+', 'name' => '[a-z]+']);
        
        $request = $this->frontendRequest('GET', '/user/a/calvin');
        $this->assertResponseBody('a:calvin', $request);
        
        $request = $this->frontendRequest('GET', '/users/b/calvin');
        $this->assertEmptyBody($request);
        
        $request = $this->frontendRequest('GET', '/users/calvin/calvin');
        $this->assertEmptyBody($request);
    }
    
    /** @test */
    public function optional_parameters_work_at_the_end_of_the_url()
    {
        $this->routeConfigurator()->get('r1',
            'users/{id}/{name?}',
            [RoutingTestController::class, 'users']
        );
        
        $request = $this->frontendRequest('GET', '/users/1/calvin');
        $this->assertResponseBody('dynamic:1:calvin', $request);
        
        $request = $this->frontendRequest('GET', 'users/1');
        $this->assertResponseBody('dynamic:1:default_user', $request);
    }
    
    /** @test */
    public function multiple_parameters_can_be_optional()
    {
        $this->routeConfigurator()->post(
            'r1',
            '/team/{name?}/{player?}',
            [RoutingTestController::class, 'twoOptional']
        );
        
        $response = $this->frontendRequest('post', '/team');
        $this->assertResponseBody('default1:default2', $response);
        
        $response = $this->frontendRequest('post', '/team/dortmund');
        $this->assertResponseBody('dortmund:default2', $response);
        
        $response = $this->frontendRequest('post', '/team/dortmund/calvin');
        $this->assertResponseBody('dortmund:calvin', $response);
    }
    
    /** @test */
    public function multiple_parameters_can_be_optional_with_a_preceding_required_segment()
    {
        // Preceding group is required but not capturing
        $this->routeConfigurator()->post(
            'r1',
            '/static/{name}/{gender?}/{age?}',
            [RoutingTestController::class, 'requiredAndOptional']
        );
        
        $response = $this->frontendRequest('post', '/static/foo');
        $this->assertResponseBody('foo:default1:default2', $response);
        
        $response = $this->frontendRequest('post', '/static/foo/bar');
        $this->assertResponseBody('foo:bar:default2', $response);
        
        $response = $this->frontendRequest('post', '/static/foo/bar/baz');
        $this->assertResponseBody('foo:bar:baz', $response);
        
        $response = $this->frontendRequest('post', '/static');
        $this->assertResponseBody('', $response);
    }
    
    /** @test */
    public function multiple_parameters_can_be_optional_and_have_custom_regex()
    {
        $this->routeConfigurator()->post(
            'r1',
            '/team/{id?}/{name?}',
            [RoutingTestController::class, 'twoOptional']
        )->requirements(['name' => '[a-z]+', 'id' => '\d+']);
        
        $response = $this->frontendRequest('post', '/team');
        $this->assertResponseBody('default1:default2', $response);
        
        $response = $this->frontendRequest('post', '/team/1');
        $this->assertResponseBody('1:default2', $response);
        
        $response = $this->frontendRequest('post', '/team/1/dortmund');
        $this->assertResponseBody('1:dortmund', $response);
        
        // Needs to be a number
        $response = $this->frontendRequest('post', '/team/fail');
        $this->assertEmptyBody($response);
        
        // seconds param can't be a number
        $response = $this->frontendRequest('post', '/team/1/12');
        $this->assertEmptyBody($response);
    }
    
    /** @test */
    public function adding_regex_can_be_done_as_a_fluent_api()
    {
        $this->routeConfigurator()->get(
            'r1',
            'users/{user_id}/{name}',
            [RoutingTestController::class, 'twoParams']
        )
             ->requirements(['user_id' => '[a]+'])
             ->requirements(['name' => 'calvin']);
        
        $request = $this->frontendRequest('GET', '/users/a/calvin');
        $this->assertResponseBody('a:calvin', $request);
        
        $request = $this->frontendRequest('GET', '/users/a/john');
        $this->assertEmptyBody($request);
        
        $request = $this->frontendRequest('GET', '/users/b/calvin');
        $this->assertEmptyBody($request);
    }
    
    /** @test */
    public function only_alpha_can_be_added_to_a_segment_as_a_helper_method()
    {
        $this->routeConfigurator()->get(
            'route1',
            '/route1/{param1}/{param2}',
            [RoutingTestController::class, 'twoParams']
        )->requireAlpha('param1');
        
        $this->routeConfigurator()->get(
            'route2',
            'route2/{param1}/{param2}',
            [RoutingTestController::class, 'twoParams']
        )->requireAlpha(['param1', 'param2']);
        
        $this->routeConfigurator()->get(
            'route3',
            '/route3/{param1}/{param2}',
            [RoutingTestController::class, 'twoParams']
        )->requireAlpha('param1', true);
        
        $request = $this->frontendRequest('GET', '/route1/foo/a');
        $this->assertResponseBody('foo:a', $request);
        
        $request = $this->frontendRequest('GET', '/route1/foo111');
        $this->assertEmptyBody($request);
        
        // uppercase not allowed by default
        $request = $this->frontendRequest('GET', '/route1/FOO/a');
        $this->assertEmptyBody($request);
        
        $request = $this->frontendRequest('GET', '/route2/foo/bar');
        $this->assertResponseBody('foo:bar', $request);
        
        $request = $this->frontendRequest('GET', '/route2/foo/+');
        $this->assertEmptyBody($request);
        
        $request = $this->frontendRequest('GET', '/route2/1/foo');
        $this->assertEmptyBody($request);
        
        // uppercase allowed explicitly
        $request = $this->frontendRequest('GET', '/route3/FOO/a');
        $this->assertResponseBody('FOO:a', $request);
    }
    
    /** @test */
    public function only_alphanumerical_can_be_added_to_a_segment_as_a_helper_method()
    {
        $this->routeConfigurator()->get(
            'route1',
            'route1/{name}',
            [RoutingTestController::class, 'dynamic']
        )
             ->requireAlphaNum('name');
        
        $this->routeConfigurator()->get(
            'route2',
            'route2/{name}',
            [RoutingTestController::class, 'dynamic']
        )
             ->requireAlphaNum('name', true);
        
        $request = $this->frontendRequest('GET', '/route1/foo');
        $this->assertResponseBody('dynamic:foo', $request);
        
        $request = $this->frontendRequest('GET', '/route1/foo123');
        $this->assertResponseBody('dynamic:foo123', $request);
        
        $request = $this->frontendRequest('GET', '/route1/foo+');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('GET', '/route1/FOO');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('GET', '/route2/FOO');
        $this->assertResponseBody('dynamic:FOO', $request);
    }
    
    /** @test */
    public function only_number_can_be_added_to_a_segment_as_a_helper_method()
    {
        $this->routeConfigurator()->get(
            'route1',
            'route1/{name}',
            [RoutingTestController::class, 'dynamicInt']
        )
             ->requireNum('name');
        
        $request = $this->frontendRequest('GET', '/route1/1');
        $this->assertResponseBody('dynamic:1', $request);
        
        $request = $this->frontendRequest('GET', '/route1/calvin');
        $this->assertEmptyBody($request);
    }
    
    /** @test */
    public function only_one_of_can_be_added_to_a_segment_as_a_helper_method()
    {
        $this->routeConfigurator()->get(
            'route1',
            'route1/{name}',
            [RoutingTestController::class, 'dynamicInt']
        )
             ->requireOneOf('name', [1, 2, 3]);
        
        $request = $this->frontendRequest('GET', '/route1/1');
        $this->assertResponseBody('dynamic:1', $request);
        
        $request = $this->frontendRequest('GET', '/route1/2');
        $this->assertResponseBody('dynamic:2', $request);
        
        $request = $this->frontendRequest('GET', '/route1/3');
        $this->assertResponseBody('dynamic:3', $request);
        
        $request = $this->frontendRequest('GET', '/route1/4');
        $this->assertResponseBody('', $request);
    }
    
}