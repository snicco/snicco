<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

/**
 * @internal
 */
final class ViewResponseTest extends TestCase
{
    use CreateTestPsr17Factories;

    private Response $base_response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_response = $this->createResponseFactory()
            ->createResponse();
    }

    /**
     * @test
     */
    public function test_is_psr(): void
    {
        $response = new ViewResponse('foo', $this->base_response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('foo', $response->view());
    }

    /**
     * @test
     */
    public function test_with_view(): void
    {
        $response = new ViewResponse('foo', $this->base_response);
        $this->assertSame('foo', $response->view());

        $new = $response->withView('bar');
        $this->assertSame('bar', $new->view());
        $this->assertSame('foo', $response->view());
    }

    /**
     * @test
     */
    public function test_view_data(): void
    {
        $response = new ViewResponse('foo', $this->base_response);
        $this->assertSame([], $response->viewData());

        $new = $response->withViewData([
            'foo' => 'bar',
        ]);
        $this->assertSame([
            'foo' => 'bar',
        ], $new->viewData());
        $this->assertSame([], $response->viewData());
    }

    /**
     * @test
     */
    public function test_html_by_default(): void
    {
        $response = new ViewResponse('foo', $this->base_response);
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
    }
}
