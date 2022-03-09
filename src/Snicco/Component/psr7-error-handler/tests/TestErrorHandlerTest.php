<?php

declare(strict_types=1);


namespace Snicco\Component\Psr7ErrorHandler\Tests;

use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\TestErrorHandler;

final class TestErrorHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function exceptions_are_rethrown(): void
    {
        $handler = new TestErrorHandler();

        $e = new Exception('foobar');

        $this->expectExceptionObject($e);

        $handler->handle($e, (new Psr17Factory())->createServerRequest('GET', '/'));
    }
}
