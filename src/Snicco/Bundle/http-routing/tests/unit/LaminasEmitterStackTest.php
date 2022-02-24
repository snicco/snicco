<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Snicco\Bundle\HttpRouting\ResponseEmitter\LaminasEmitterStack;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

use function function_exists;
use function xdebug_get_headers;

final class LaminasEmitterStackTest extends TestCase
{

    /**
     * @test
     *
     * @runInSeparateProcess
     */
    public function test_sapi_emitter_is_used(): void
    {
        $factory = new Psr17Factory();

        $response = $factory->createResponse()->withBody($factory->createStream('foo'))
            ->withHeader('X-FOO', 'bar')
            ->withAddedHeader('set-cookie', 'cookie1=val')
            ->withAddedHeader('set-cookie', 'cookie2=val');

        $emitter = new LaminasEmitterStack();

        $this->expectOutputString('foo');

        $emitter->emit(new Response($response));

        if (function_exists('xdebug_get_headers')) {
            $this->assertSame(
                ['X-FOO: bar', 'Set-Cookie: cookie1=val', 'Set-Cookie: cookie2=val'],
                xdebug_get_headers()
            );
        }
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     */
    public function test_stream_emitter_works_for_content_disposition(): void
    {
        $factory = new Psr17Factory();

        $response = $factory->createResponse()->withBody($factory->createStream('foo'))
            ->withHeader('X-FOO', 'bar')
            ->withAddedHeader('set-cookie', 'cookie1=val')
            ->withAddedHeader('set-cookie', 'cookie2=val')
            ->withHeader('content-disposition', 'attachment');

        $emitter = new LaminasEmitterStack();

        $this->expectOutputString('foo');

        $emitter->emit(new Response($response));

        if (function_exists('xdebug_get_headers')) {
            $this->assertSame(
                ['X-FOO: bar', 'Set-Cookie: cookie1=val', 'Set-Cookie: cookie2=val', 'Content-Disposition: attachment'],
                xdebug_get_headers()
            );
        }
    }


}