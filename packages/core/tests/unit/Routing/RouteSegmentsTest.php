<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Snicco\Http\Psr7\Request as Request;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Snicco\Routing\Conditions\QueryStringCondition;

class RouteSegmentsTest extends RoutingTestCase
{
    
    /** @test */
    public function url_encoded_routes_work()
    {
        $this->createRoutes(function () {
            $this->router->get('/german-city/{city}', function (Request $request, string $city) {
                return ucfirst($city);
            });
        });
        
        $path = rawurlencode('münchen');
        $request = TestRequest::fromFullUrl('GET', "https://foobar.com/german-city/$path");
        $this->assertResponse('München', $request);
    }
    
    /** @test */
    public function url_encoded_query_string_conditions_work()
    {
        $this->createRoutes(function () {
            $this->router->get('*', function () {
                return 'FOO';
            })->where(QueryStringCondition::class, ['page' => 'bayern münchen']);
        });
        
        $query = urlencode('bayern münchen');
        $request = TestRequest::fromFullUrl('GET', "https://foobar.com/foo?page=$query");
        $request = $request->withQueryParams(['page' => 'bayern münchen']);
        $this->assertResponse('FOO', $request);
    }
    
    /** @test */
    public function route_segments_can_contain_encoded_forward_slashes()
    {
        $this->createRoutes(function () {
            $this->router->get(
                '/bands/{band}/{song?}',
                function (string $band, string $song = null) {
                    if ($song) {
                        return "Show song [$song] of band [$band]";
                    }
                    
                    return "List all songs of band [$band]";
                }
            );
        });
        
        $request = TestRequest::fromFullUrl('GET', 'https://music.com/bands/AC%2fDC/foo_song');
        $this->assertResponse(
            'Show song [foo_song] of band [AC/DC]',
            $request
        );
        
        $request = TestRequest::fromFullUrl('GET', 'https://music.com/bands/AC%2fDC');
        $this->assertResponse(
            'List all songs of band [AC/DC]',
            $request
        );
    }
    
    /** @test */
    public function regex_can_be_added_as_a_condition_without_needing_array_syntax()
    {
        $this->createRoutes(function () {
            $this->router->get('users/{user}', function () {
                return 'foo';
            })->and('user', '[0-9]+');
        });
        
        $request = $this->frontendRequest('GET', '/users/1');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/users/calvin');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function regex_can_be_added_as_a_condition_as_array_syntax()
    {
        $this->createRoutes(function () {
            $this->router->get('users/{user}', function () {
                return 'foo';
            })->and(['user', '[0-9]+']);
        });
        
        $request = $this->frontendRequest('GET', '/users/1');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/users/calvin');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function multiple_regex_conditions_can_be_added_to_an_url_condition()
    {
        $this->createRoutes(function () {
            $this->router->get('/user/{id}/{name}', function (Request $request, $id, $name) {
                return $name.$id;
            })->and(['id' => '[0-9]+', 'name' => '[a-z]+']);
        });
        
        $request = $this->frontendRequest('GET', '/user/1/calvin');
        $this->assertResponse('calvin1', $request);
        
        $request = $this->frontendRequest('GET', '/users/1/1');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', '/users/calvin/calvin');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function required_parameters_dont_match_trailing_slashes_by_default()
    {
        $this->createRoutes(function () {
            $this->router->get(
                'users/{id}',
                function (Request $request, $id) {
                    return (string) $id;
                }
            );
        }, false);
        
        $request = $this->frontendRequest('GET', '/users/1');
        $this->assertResponse('1', $request);
        
        $request = $this->frontendRequest('GET', 'users/1/');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function required_parameters_can_match_trailing_slashes_only()
    {
        $this->createRoutes(function () {
            $this->router->get(
                'users/{id}',
                function (Request $request, $id) {
                    return (string) $id;
                }
            );
        }, true);
        
        $request = $this->frontendRequest('GET', '/users/1');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', 'users/1/');
        $this->assertResponse('1', $request);
    }
    
    /** @test */
    public function optional_parameters_work_at_the_end_of_the_url()
    {
        $this->createRoutes(function () {
            $this->router->get(
                'users/{id}/{name?}',
                function (Request $request, $id, $name = 'admin') {
                    return $name.$id;
                }
            );
        });
        
        $request = $this->frontendRequest('GET', '/users/1/calvin');
        $this->assertResponse('calvin1', $request);
        
        $request = $this->frontendRequest('GET', 'users/1');
        $this->assertResponse('admin1', $request);
    }
    
    /** @test */
    public function multiple_parameters_can_be_optional_with_a_preceding_capturing_group()
    {
        $this->createRoutes(function () {
            // Preceding Group is capturing
            $this->router->post('/team/{id:\d+}/{name?}/{player?}')
                         ->handle(
                             function (Request $request, $id, $name = 'foo_team', $player = 'foo_player') {
                                 return $name.':'.$id.':'.$player;
                             }
                         );
        });
        
        $response = $this->frontendRequest('post', '/team/1/dortmund/calvin');
        $this->assertResponse('dortmund:1:calvin', $response);
        
        $response = $this->frontendRequest('post', '/team/1/dortmund');
        $this->assertResponse('dortmund:1:foo_player', $response);
        
        $response = $this->frontendRequest('post', '/team/12');
        $this->assertResponse('foo_team:12:foo_player', $response);
    }
    
    /** @test */
    public function multiple_params_can_be_optional_with_preceding_non_capturing_group()
    {
        $this->createRoutes(function () {
            // Preceding group is required but not capturing
            $this->router->post('/users/{name?}/{gender?}/{age?}')
                         ->handle(
                             function (Request $request, $name = 'john', $gender = 'm', $age = '21') {
                                 return $name.':'.$gender.':'.$age;
                             }
                         );
        });
        
        $response = $this->frontendRequest('post', '/users/calvin/male/23');
        $this->assertResponse('calvin:male:23', $response);
        
        $response = $this->frontendRequest('post', '/users/calvin/male');
        $this->assertResponse('calvin:male:21', $response);
        
        $response = $this->frontendRequest('post', '/users/calvin');
        $this->assertResponse('calvin:m:21', $response);
        
        $response = $this->frontendRequest('post', '/users');
        $this->assertResponse('john:m:21', $response);
    }
    
    /** @test */
    public function routes_with_optional_params_will_not_match_routes_with_requests_with_trailing_slashes_by_default()
    {
        $this->createRoutes(function () {
            // Preceding group is required but not capturing
            $this->router->post('/users/{name?}/{gender?}/{age?}')
                         ->handle(
                             function (Request $request, $name = 'john', $gender = 'm', $age = '21') {
                                 return $name.':'.$gender.':'.$age;
                             }
                         );
        }, false);
        
        // None of these should match because we force trailing slashes
        
        $request = $this->frontendRequest('post', '/users/');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('post', '/users/calvin/');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('post', '/users/calvin/male/');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('post', '/users/calvin/male/23/');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('post', '/users/calvin');
        $this->assertResponse('calvin:m:21', $request);
        
        $request = $this->frontendRequest('post', '/users/calvin/male');
        $this->assertResponse('calvin:male:21', $request);
        
        $request = $this->frontendRequest('post', '/users/calvin/male/23');
        $this->assertResponse('calvin:male:23', $request);
    }
    
    /**
     * @note When optional segments have custom regex AND TRAILING SLASHES ARE USED this suffix has
     *     to be added "\/?"
     * @test
     */
    public function optional_params_can_match_only_with_trailing_slash_if_desired()
    {
        $this->createRoutes(function () {
            // Preceding group is required but not capturing
            $this->router->post('/users/{name?}/{gender?}/{age?}')
                         ->and(
                             [
                                 'name' => '[a-z]+\/?',
                             ]
                         )
                         ->handle(
                             function (Request $request, $name = 'john', $gender = 'm', $age = '21') {
                                 return $name.':'.$gender.':'.$age;
                             }
                         );
        }, true);
        
        $request = $this->frontendRequest('post', '/users/');
        $this->assertResponse('john:m:21', $request);
        
        $request = $this->frontendRequest('post', '/users/calvin/');
        $this->assertResponse('calvin:m:21', $request);
        
        $request = $this->frontendRequest('post', '/users/1/');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('post', '/users/calvin/male/');
        $this->assertResponse('calvin:male:21', $request);
        
        $request = $this->frontendRequest('post', '/users/calvin/male/23/');
        $this->assertResponse('calvin:male:23', $request);
        
        // None of these should match because we force trailing slashes
        $request = $this->frontendRequest('post', '/users/calvin');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('post', '/users/calvin/male');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('post', '/users/calvin/male/23');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('post', '/users');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function optional_parameters_work_with_our_custom_api()
    {
        $this->createRoutes(function () {
            $this->router->get(
                'users/{id}/{name?}',
                function (Request $request, $id, $name = 'admin') {
                    return $name.$id;
                }
            )->and('name', '[a-z]+');
        });
        
        $request = $this->frontendRequest('GET', '/users/1/calvin');
        $this->assertResponse('calvin1', $request);
        
        $request = $this->frontendRequest('GET', 'users/1');
        $this->assertResponse('admin1', $request);
        
        $request = $this->frontendRequest('GET', 'users/1/12');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function multiple_parameters_can_be_optional_and_have_custom_regex()
    {
        $this->createRoutes(function () {
            // Preceding Group is capturing
            $this->router->post('/team/{id}/{name?}/{age?}')
                         ->and(['name' => '[a-z]+', 'age' => '\d+'])
                         ->handle(function (Request $request, $id, $name = 'foo_team', $age = 21) {
                             return $name.':'.$id.':'.$age;
                         });
        });
        
        $response = $this->frontendRequest('post', '/team/1/dortmund/23');
        $this->assertResponse('dortmund:1:23', $response);
        
        $response = $this->frontendRequest('post', '/team/1/dortmund');
        $this->assertResponse('dortmund:1:21', $response);
        
        $response = $this->frontendRequest('post', '/team/12');
        $this->assertResponse('foo_team:12:21', $response);
        
        $response = $this->frontendRequest('post', '/team/1/dortmund/fail');
        $this->assertEmptyResponse($response);
        
        $response = $this->frontendRequest('post', '/team/1/123/123');
        $this->assertEmptyResponse($response);
    }
    
    /** @test */
    public function adding_regex_can_be_done_as_a_fluent_api()
    {
        $this->createRoutes(function () {
            $this->router->get('users/{user_id}/{name}', function () {
                return 'foo';
            })->and('user_id', '[0-9]+')->and('name', 'calvin');
        });
        
        $request = $this->frontendRequest('GET', '/users/1/calvin');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/users/1/john');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', '/users/w/calvin');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function only_alpha_can_be_added_to_a_segment_as_a_helper_method()
    {
        $this->createRoutes(function () {
            $this->router->get('users/{name}', function () {
                return 'foo';
            })->andAlpha('name');
            
            $this->router->get('teams/{name}/{player}', function () {
                return 'foo';
            })->andAlpha('name', 'player');
            
            $this->router->get('countries/{country}/{city}', function () {
                return 'foo';
            })->andAlpha(['country', 'city']);
        });
        
        $request = $this->frontendRequest('GET', '/users/calvin');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/users/cal1vin');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/teams/1/calvin');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/1');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', '/countries/germany/berlin');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/countries/germany/1');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', '/countries/1/berlin');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function only_alphanumerical_can_be_added_to_a_segment_as_a_helper_method()
    {
        $this->createRoutes(function () {
            $this->router->get('users/{name}', function () {
                return 'foo';
            })->andAlphaNumerical('name');
        });
        
        $request = $this->frontendRequest('GET', '/users/calvin');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/users/calv1in');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function only_number_can_be_added_to_a_segment_as_a_helper_method()
    {
        $this->createRoutes(function () {
            $this->router->get('users/{name}', function () {
                return 'foo';
            })->andNumber('name');
        });
        
        $request = $this->frontendRequest('GET', '/users/1');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/users/calvin');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', '/users/calv1in');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function only_one_of_can_be_added_to_a_segment_as_a_helper_method()
    {
        $this->createRoutes(function () {
            $this->router->get('home/{locale}', function (Request $request, $locale) {
                return $locale;
            })->andEither('locale', ['en', 'de']);
        });
        
        $request = $this->frontendRequest('GET', '/home/en');
        $this->assertResponse('en', $request);
        
        $request = $this->frontendRequest('GET', '/home/de');
        $this->assertResponse('de', $request);
        
        $request = $this->frontendRequest('GET', '/home/es');
        $this->assertEmptyResponse($request);
    }
    
}