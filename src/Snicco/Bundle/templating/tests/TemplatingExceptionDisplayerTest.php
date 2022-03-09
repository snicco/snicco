<?php

declare(strict_types=1);

namespace Snicco\Bundle\Templating\Tests;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Snicco\Bundle\Templating\TemplatingExceptionDisplayer;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewEngine;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;
use Snicco\Component\Templating\ViewFactory\PHPViewFinder;

final class TemplatingExceptionDisplayerTest extends TestCase
{
    private TemplatingExceptionDisplayer $displayer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->displayer = new TemplatingExceptionDisplayer(
            new ViewEngine(
                new PHPViewFactory(
                    new PHPViewFinder([__DIR__ . '/fixtures/templates']),
                    new ViewComposerCollection()
                )
            )
        );
    }

    /**
     * @test
     */
    public function test_supportedContentType(): void
    {
        $this->assertSame('text/html', $this->displayer->supportedContentType());
    }

    /**
     * @test
     */
    public function test_isVerbose(): void
    {
        $this->assertSame(false, $this->displayer->isVerbose());
    }

    /**
     * @test
     */
    public function test_canDisplay(): void
    {
        $this->assertTrue(
            $this->displayer->canDisplay(
                $this->getInformation(500, 'foo_id')
            )
        );

        $this->assertFalse(
            $this->displayer->canDisplay(
                $this->getInformation(404, 'bar_id')
            )
        );

        // errors.403
        $this->assertTrue(
            $this->displayer->canDisplay(
                $this->getInformation(403, 'baz_id')
            )
        );

        // exceptions.406
        $this->assertTrue(
            $this->displayer->canDisplay(
                $this->getInformation(406, 'biz_id')
            )
        );
    }

    /**
     * @test
     */
    public function test_display(): void
    {
        $information = $this->getInformation(500, 'foo_id');

        $this->assertTrue($this->displayer->canDisplay($information));

        $content = $this->displayer->display($information);

        $this->assertStringContainsString('Title: foo_title', $content);
        $this->assertStringContainsString('Details: foo_details', $content);
        $this->assertStringContainsString('Status: 500', $content);
        $this->assertStringContainsString('Identifier: foo_id', $content);
    }

    /**
     * @test
     */
    public function test_display_with_admin_request_has_priority(): void
    {
        $psr_request = new ServerRequest('GET', '/');
        $admin_request = Request::fromPsr($psr_request, Request::TYPE_ADMIN_AREA);
        $frontend_request = Request::fromPsr($psr_request);

        $information = $this->getInformation(500, 'foo_id', $admin_request);

        $this->assertTrue($this->displayer->canDisplay($information));

        $content = $this->displayer->display($information);

        $this->assertStringContainsString('Admin', $content);
        $this->assertStringContainsString('Title: foo_title', $content);
        $this->assertStringContainsString('Details: foo_details', $content);
        $this->assertStringContainsString('Status: 500', $content);
        $this->assertStringContainsString('Identifier: foo_id', $content);

        $information = $this->getInformation(403, 'bar_id', $admin_request);
        $content = $this->displayer->display($information);
        $this->assertStringContainsString('403-admin', $content);

        $information = $this->getInformation(403, 'baz_id', $frontend_request);
        $content = $this->displayer->display($information);
        $this->assertStringContainsString('403-frontend', $content);
    }

    private function getInformation(
        int $status_code,
        string $id,
        ServerRequestInterface $request = null
    ): ExceptionInformation {
        return new ExceptionInformation(
            $status_code,
            $id,
            'foo_title',
            'foo_details',
            $e = new RuntimeException('secret'),
            $e,
            $request ?: new ServerRequest('GET', '/')
        );
    }
}
