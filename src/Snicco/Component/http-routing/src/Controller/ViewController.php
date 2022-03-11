<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Webmozart\Assert\Assert;

use function array_keys;
use function array_slice;

/**
 * @psalm-internal Snicco\Component\HttpRouting
 *
 * @interal
 */
final class ViewController extends Controller
{
    /**
     * @param mixed ...$args
     */
    public function __invoke(...$args): Response
    {
        [$view, $data, $status, $headers] = array_slice($args, -4);

        Assert::stringNotEmpty($view);
        Assert::integer($status);
        Assert::isArray($data);
        Assert::isArray($headers);
        Assert::allString(array_keys($data));

        /** @var array<string,mixed> $data */
        $response = $this->respondWith()
            ->view($view, $data);

        /**
         * @var array<string,string> $headers
         */
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
