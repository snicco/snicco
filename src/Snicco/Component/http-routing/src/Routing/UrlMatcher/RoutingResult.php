<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Routing\Route\Route;

use function array_map;
use function is_numeric;
use function rawurldecode;

final class RoutingResult
{
    private ?Route $route;

    /**
     * @var array<string,string>
     */
    private array $captured_segments = [];

    /**
     * @var array<string,int|string>
     */
    private ?array $decoded_segments = null;

    /**
     * @param array<string,string> $captured_segments
     */
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

    /**
     * @return array<string,string>
     */
    public function capturedSegments(): array
    {
        return $this->captured_segments;
    }

    /**
     * @return array<string,int|string>
     */
    public function decodedSegments(): array
    {
        if (! isset($this->decoded_segments)) {
            $this->decoded_segments = array_map(function (string $value) {
                if (is_numeric($value)) {
                    return (int) $value;
                }

                return rawurldecode($value);
            }, $this->captured_segments);
        }

        return $this->decoded_segments;
    }

    /**
     * @param array<string,string> $segments
     */
    public function withCapturedSegments(array $segments): RoutingResult
    {
        return new self($this->route, $segments);
    }

    /**
     * @param array<string,string> $captured_segments
     */
    public static function match(Route $route, array $captured_segments = []): RoutingResult
    {
        return new self($route, $captured_segments);
    }
}
