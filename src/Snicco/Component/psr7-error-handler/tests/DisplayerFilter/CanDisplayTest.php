<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\DisplayerFilter;

use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\CanDisplay;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function array_values;

final class CanDisplayTest extends TestCase
{
    /**
     * @test
     */
    public function all_displayers_that_can_display_are_included(): void
    {
        $filter = new CanDisplay();
        $displayers = [
            $d1 = new CanDisplayRuntimeException(),
            $d2 = new CanDisplayRuntimeException2(),
            $d3 = new CanDisplayInvalidArgException(),
        ];

        $e = new RuntimeException();
        $request = new ServerRequest('GET', '/foo');

        $filtered = $filter->filter(
            $displayers,
            $request,
            new ExceptionInformation(500, 'foo_id', 'foo_title', 'foo_details', $e, $e, $request)
        );

        $this->assertSame([$d1, $d2], $filtered);

        $e = new InvalidArgumentException();

        $filtered = $filter->filter(
            $displayers,
            $request,
            new ExceptionInformation(500, 'foo_id', 'foo_title', 'foo_details', $e, $e, $request)
        );

        $this->assertSame([$d3], array_values($filtered));
    }
}

class CanDisplayRuntimeException implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return 'runtime';
    }

    public function supportedContentType(): string
    {
        return 'text/plain';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return $exception_information->originalException() instanceof RuntimeException;
    }
}

class CanDisplayRuntimeException2 implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
    }

    public function supportedContentType(): string
    {
        return 'text/plain';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return $exception_information->originalException() instanceof RuntimeException;
    }
}

class CanDisplayInvalidArgException implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
    }

    public function supportedContentType(): string
    {
        return 'text/plain';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return $exception_information->originalException() instanceof InvalidArgumentException;
    }
}
