<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\HttpKernel;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\ResponsePreparation;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\Psr7ErrorHandler\TestErrorHandler;

final class HttpKernelTest extends TestCase
{

    /**
     * @test
     */
    public function test_exception_if_no_response_is_returned_in_middleware_pipeline(): void
    {
        $pipeline = new MiddlewarePipeline(
            new PimpleContainerAdapter(), new TestErrorHandler()
        );

        $http_kernel = new HttpKernel(
            $pipeline,
            new ResponsePreparation(new Psr17Factory()),
            new BaseEventDispatcher(),
            []
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('exhausted');

        $http_kernel->handle(Request::fromPsr(new ServerRequest('GET', '/')));
    }

}