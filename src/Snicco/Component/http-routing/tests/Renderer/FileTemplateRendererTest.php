<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Exception\CouldNotRenderTemplate;
use Snicco\Component\HttpRouting\Renderer\FileTemplateRenderer;

final class FileTemplateRendererTest extends TestCase
{

    /**
     * @test
     */
    public function test_exception_for_bad_file(): void
    {
        $renderer = new FileTemplateRenderer();

        $this->expectException(CouldNotRenderTemplate::class);
        $renderer->render('foo');
    }

}