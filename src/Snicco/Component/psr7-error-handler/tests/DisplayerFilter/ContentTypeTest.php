<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\DisplayerFilter;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\ContentType;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\PlainTextExceptionDisplayer2;

use function array_values;

/**
 * @internal
 */
final class ContentTypeTest extends TestCase
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
        $filter = new ContentType();
        $displayers = [
            $d1 = new PlaintTextExceptionDisplayer1(),
            $d2 = new PlainTextExceptionDisplayer2(),
            $d3 = new JsonExceptionDisplayer1(),
            $d4 = new JsonExceptionDisplayer2(),
        ];

        $e = new RuntimeException();
        $info = new ExceptionInformation(500, 'foo_id', 'foo_title', 'foo_details', $e, $e, $this->request);
        $request = new ServerRequest('GET', '/foo');

        $filtered = $filter->filter($displayers, $request->withHeader('Accept', 'text/plain'), $info,);

        $this->assertSame([$d1, $d2], array_values($filtered));

        $filtered = $filter->filter($displayers, $request->withHeader('Accept', 'application/json'), $info,);

        $this->assertSame([$d3, $d4], array_values($filtered));
    }

    /**
     * @test
     */
    public function the_content_type_is_only_parsed_for_the_first_mime_type(): void
    {
        $filter = new ContentType();
        $displayers = [$d1 = new PlaintTextExceptionDisplayer1(), $d2 = new JsonExceptionDisplayer1()];

        $e = new RuntimeException();
        $info = new ExceptionInformation(500, 'foo_id', 'foo_title', 'foo_details', $e, $e, $this->request);
        $request = new ServerRequest('GET', '/foo');

        // No content negotiation is performed
        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'text/plain;q=0.1, application/json:q=0.9'),
            $info,
        );

        $this->assertSame([$d1], array_values($filtered));

        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'application/json;q=0.1, text/plain:q=0.9'),
            $info,
        );

        $this->assertSame([$d2], array_values($filtered));
    }
}

final class PlaintTextExceptionDisplayer1 implements ExceptionDisplayer
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

final class PlaintTextExceptionDisplayer2 implements ExceptionDisplayer
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

final class JsonExceptionDisplayer1 implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return '';
    }

    public function supportedContentType(): string
    {
        return 'application/json';
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

final class JsonExceptionDisplayer2 implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return '';
    }

    public function supportedContentType(): string
    {
        return 'application/json';
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
