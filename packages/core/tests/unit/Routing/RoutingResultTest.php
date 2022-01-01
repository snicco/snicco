<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Core\Routing\Route;
use PHPUnit\Framework\TestCase;
use Snicco\Core\Routing\RoutingResult;

class RoutingResultTest extends TestCase
{
    
    /** @test */
    public function captured_segments_are_returned_as_is()
    {
        $result = new RoutingResult(
            $route = $this->route(),
            $arr = ['foo' => 'foo%20bar', 'bar' => '1']
        );
        
        $this->assertSame($route, $result->route());
        $this->assertSame($arr, $result->capturedSegments());
    }
    
    /** @test */
    public function decoded_segments_convert_integerish_strings_to_numbers()
    {
        $routing_result = new RoutingResult($this->route(), ['foo' => 'foo%20bar', 'bar' => '1']);
        
        $this->assertSame(['foo' => 'foo bar', 'bar' => 1], $routing_result->decodedSegments());
    }
    
    /** @test */
    public function with_captured_segments_is_immutable()
    {
        $res1 = new RoutingResult($this->route(), ['foo' => 'foo%20bar', 'bar' => '1']);
        
        $res2 = $res1->withCapturedSegments($res1->capturedSegments());
        
        $this->assertSame(['foo' => 'foo bar', 'bar' => 1], $res2->decodedSegments());
        $this->assertSame($res1->route(), $res2->route());
        
        $this->assertNotSame($res1, $res2);
    }
    
    private function route() :Route
    {
        return Route::create('/foo', Route::DELEGATE, 'foo');
    }
    
}
