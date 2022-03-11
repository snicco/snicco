<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;

/**
 * @internal
 */
final class RoutingResultTest extends TestCase
{
    /**
     * @test
     */
    public function captured_segments_are_returned_as_is(): void
    {
        $result = RoutingResult::match($route = $this->route(), $arr = [
            'foo' => 'foo%20bar',
            'bar' => '1',
        ]);

        $this->assertSame($route, $result->route());
        $this->assertSame($arr, $result->capturedSegments());
    }

    /**
     * @test
     */
    public function decoded_segments_convert_integerish_strings_to_numbers(): void
    {
        $routing_result =
            RoutingResult::match($this->route(), [
                'foo' => 'foo%20bar',
                'bar' => '1',
            ]);

        $this->assertSame([
            'foo' => 'foo bar',
            'bar' => 1,
        ], $routing_result->decodedSegments());
    }

    /**
     * @test
     */
    public function with_captured_segments_is_immutable(): void
    {
        $res1 = RoutingResult::match($this->route(), [
            'foo' => 'foo%20bar',
            'bar' => '1',
        ]);

        $res2 = $res1->withCapturedSegments($res1->capturedSegments());

        $this->assertSame([
            'foo' => 'foo bar',
            'bar' => 1,
        ], $res2->decodedSegments());
        $this->assertSame($res1->route(), $res2->route());

        $this->assertNotSame($res1, $res2);
    }

    /**
     * @test
     */
    public function test_no_match(): void
    {
        $res = RoutingResult::noMatch();

        $this->assertNull($res->route());
        $this->assertSame([], $res->capturedSegments());
        $this->assertSame([], $res->decodedSegments());
    }

    private function route(): Route
    {
        return Route::create('/foo', Route::DELEGATE, 'foo');
    }
}
