<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\DisplayerFilter;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Verbosity;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function array_values;

/**
 * @internal
 */
final class VerbosityTest extends TestCase
{
    private ServerRequest $request;

    protected function setUp(): void
    {
        $this->request = new ServerRequest('GET', '/');
        parent::setUp();
    }

    /**
     * @test
     */
    public function all_displayers_that_can_display_are_included(): void
    {
        $filter = new Verbosity(true);
        $displayers = [
            $d1 = new Verbose1(),
            $d2 = new Verbose2(),
            $d3 = new NonVerbose1(),
            $d4 = new NonVerbose2(),
        ];

        $e = new RuntimeException();
        $info = new ExceptionInformation(
            500,
            'foo_id',
            'foo_title',
            'foo_details',
            $e,
            $e,
            $this->request
        );
        $request = new ServerRequest('GET', '/foo');

        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'text/plain'),
            $info,
        );

        $this->assertSame([$d1, $d2, $d3, $d4], array_values($filtered));

        $filter = new Verbosity(false);
        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'text/plain'),
            $info,
        );

        $this->assertSame([$d3, $d4], array_values($filtered));
    }
}

class Verbose1 implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return '';
    }

    public function supportedContentType(): string
    {
        return 'text/plain';
    }

    public function isVerbose(): bool
    {
        return true;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return true;
    }
}

class Verbose2 implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return '';
    }

    public function supportedContentType(): string
    {
        return 'text/plain';
    }

    public function isVerbose(): bool
    {
        return true;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return true;
    }
}

class NonVerbose1 implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return '';
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
        return true;
    }
}

class NonVerbose2 implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return '';
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
        return true;
    }
}
