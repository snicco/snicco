<?php

declare(strict_types=1);


namespace Snicco\Component\Psr7ErrorHandler\Tests\Displayer;

use Exception;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\Displayer\FallbackJsonDisplayer;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProviderWithTransformation;

final class FallbackJsonDisplayerTest extends TestCase
{
    /**
     * @test
     */
    public function test_can_always_display(): void
    {
        $info = InformationProviderWithTransformation::fromDefaultData(new SplHashIdentifier());
        $request = new ServerRequest('GET', '/');

        $this->assertTrue((new FallbackJsonDisplayer())->canDisplay($info->createFor(new Exception(), $request)));
    }

    /**
     * @test
     */
    public function test_is_not_verbose(): void
    {
        $this->assertFalse((new FallbackJsonDisplayer())->isVerbose());
    }

    /**
     * @test
     */
    public function test_displays_html(): void
    {
        $this->assertSame('application/json', (new FallbackJsonDisplayer())->supportedContentType());
    }
}
