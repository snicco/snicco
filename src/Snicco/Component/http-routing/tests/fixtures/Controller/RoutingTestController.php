<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Controller;

use Snicco\Component\HttpRouting\Http\Psr7\Request;

use function sprintf;

final class RoutingTestController
{
    /**
     * @var string
     */
    public const static = 'static';

    /**
     * @var string
     */
    public const dynamic = 'dynamic';

    public function __invoke(): string
    {
        return $this->static();
    }

    public function static(): string
    {
        return 'static';
    }

    public function dynamic(string $param): string
    {
        return 'dynamic:' . $param;
    }

    public function dynamicInt(int $param): string
    {
        return 'dynamic:' . (string) $param;
    }

    public function twoParams(string $param1, string $param2): string
    {
        return $param1 . ':' . $param2;
    }

    public function twoParamsWithRequest(Request $request, string $param1, string $param2): string
    {
        return $param1 . ':' . $param2;
    }

    public function requestParamsConditionArgs(
        Request $request,
        string $param1,
        string $param2,
        string $condition_arg
    ): string {
        return $param1 . ':' . $param2 . ':' . $condition_arg;
    }

    /**
     * @param string|int $param1
     */
    public function twoOptional($param1 = 'default1', string $param2 = 'default2'): string
    {
        return sprintf('%s:%s', (string) $param1, $param2);
    }

    public function requiredAndOptional(
        string $param1,
        string $param2 = 'default1',
        string $param3 = 'default2'
    ): string {
        return sprintf('%s:%s:%s', $param1, $param2, $param3);
    }

    public function users(int $id, string $name = 'default_user'): string
    {
        return sprintf('dynamic:%d:%s', $id, $name);
    }

    public function bandSong(string $band, string $song = null): string
    {
        if ($song) {
            return sprintf('Show song [%s] of band [%s].', $song, $band);
        }

        return sprintf('Show all songs of band [%s].', $band);
    }

    public function onlyRequest(Request $request): string
    {
        return $this->static();
    }

    public function fallback(string $path): string
    {
        return 'fallback:' . $path;
    }

    public function returnFullRequest(Request $request): string
    {
        return (string) $request->getUri();
    }
}
