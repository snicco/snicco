<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Exception;

use InvalidArgumentException;
use Throwable;

use function array_unshift;
use function implode;

final class MiddlewareRecursion extends InvalidArgumentException
{
    /**
     * @var string[]
     */
    private array $build_trace = [];

    private string $first_duplicate;

    /**
     * @param string[] $build_trace
     */
    public function __construct(array $build_trace, string $first_duplicate, Throwable $prev = null)
    {
        $this->build_trace = $build_trace;
        $this->first_duplicate = $first_duplicate;
        $collapsed = implode('->', $build_trace) . '->' . $first_duplicate;

        parent::__construct(sprintf('Detected middleware recursion: %s', $collapsed), 0, $prev);
    }

    /**
     * @param array<string> $build_trace
     */
    public static function becauseRecursionWasDetected(array $build_trace, string $first_duplicate): MiddlewareRecursion
    {
        return new self($build_trace, $first_duplicate);
    }

    public function withFirstMiddleware(string $first): MiddlewareRecursion
    {
        $trace = $this->build_trace;
        array_unshift($trace, $first);

        return new self($trace, $this->first_duplicate, $this);
    }
}
