<?php

declare(strict_types=1);

namespace Tests\unit\Support;

use Tests\UnitTest;
use Snicco\Support\UrlParser;

class UrlParserTest extends UnitTest
{
    
    /** @test */
    public function a_url_gets_normalized_correctly_for_use_with_the_router()
    {
        $input = '/{country:name}/teams/{team:slug}';
        
        $expected = '/{country}/teams/{team}';
        
        $this->assertSame($expected, UrlParser::normalize($input));
    }
    
    /** @test */
    public function the_dynamic_route_segments_can_be_parsed()
    {
        $input = 'https://foobar.com/users/{user}';
        
        $expected = ['user'];
        
        $this->assertSame($expected, UrlParser::requiredSegments($input));
        
        $input = 'https://foobar.com/users/{user}/{account}';
        
        $expected = ['user', 'account'];
        
        $this->assertSame($expected, UrlParser::requiredSegments($input));
        
        $input = 'https://foobar.com/users/{user?}/{account}';
        
        $expected = ['account'];
        
        $this->assertSame($expected, UrlParser::requiredSegments($input));
    }
    
}
