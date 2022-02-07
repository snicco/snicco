<?php

declare(strict_types=1);


namespace Snicco\Component\SignedUrl\Tests;

use PHPUnit\Framework\TestCase;

use function dirname;
use function ob_get_clean;
use function ob_start;

final class GenerateScripTest extends TestCase
{
    /**
     * @test
     */
    public function test_generate_script(): void
    {
        ob_start();
        require_once dirname(__DIR__) . '/bin/generate-signed-url-secret.php';
        $res = (string)ob_get_clean();

        $this->assertStringContainsString("Store the following secret securely outside your webroot.\n", $res);
        $this->assertStringContainsString("You should NEVER commit is secret into VCS.\n", $res);
        $this->assertStringContainsString('32|', $res);
    }
}