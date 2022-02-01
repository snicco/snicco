<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Routing\Route\Route;

use function array_map;
use function intval;
use function is_numeric;
use function is_string;
use function rawurldecode;

/**
 * @api This class can be used to change the route parameters and perform some transformations.
 */
final class RoutingResult
{

    private ?Route $route;
    private array $captured_segments;
    private array $decoded_segments;

    private function __construct(?Route $route = null, array $captured_segments = [])
    {
        $this->route = $route;
        $this->captured_segments = $captured_segments;
    }

    public static function noMatch(): RoutingResult
    {
        return new self();
    }

    public function route(): ?Route
    {
        return $this->route;
    }

    public function isMatch(): bool
    {
        return $this->route instanceof Route;
    }

    public function capturedSegments(): array
    {
        return $this->captured_segments;
    }

    /**
     * @return array<string,mixed>
     */
    public function decodedSegments(): array
    {
        if (!isset($this->decoded_segments)) {
            $this->decoded_segments = array_map(function ($value) {
                $value = (is_string($value)) ? rawurldecode($value) : $value;

                if (is_numeric($value)) {
                    $value = intval($value);
                }
                return $value;
            }, $this->captured_segments);
        }

        return $this->decoded_segments;
    }

    /**
     * @param array<string,string|int> $segments
     *
     * @return self
     */
    public function withCapturedSegments(array $segments): self
    {
        return RoutingResult::match($this->route, $segments);
    }

    public static function match(Route $route, array $captured_segments = []): RoutingResult
    {
        return new self($route, $captured_segments);
    }

}