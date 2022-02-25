<?php

declare(strict_types=1);


namespace Snicco\Component\Psr7ErrorHandler\Tests\Displayer;

use Exception;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\Displayer\FallbackHtmlDisplayer;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProviderWithTransformation;

final class FallbackHtmlDisplayerTest extends TestCase
{

    /**
     * @test
     */
    public function test_can_always_display(): void
    {
        $info = InformationProviderWithTransformation::fromDefaultData(new SplHashIdentifier());

        $this->assertTrue((new FallbackHtmlDisplayer())->canDisplay($info->createFor(new Exception())));
    }

    /**
     * @test
     */
    public function test_is_not_verbose(): void
    {
        $this->assertFalse((new FallbackHtmlDisplayer())->isVerbose());
    }

    /**
     * @test
     */
    public function test_displays_html(): void
    {
        $this->assertSame('text/html', (new FallbackHtmlDisplayer())->supportedContentType());
    }

}