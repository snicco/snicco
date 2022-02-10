<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Snicco\Component\HttpRouting\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\TemplateRenderer;

/**
 * @interal
 */
final class ViewController extends Controller
{

    private TemplateRenderer $creates_views;

    public function __construct(TemplateRenderer $creates_views)
    {
        $this->creates_views = $creates_views;
    }

    /**
     * @param mixed ...$args
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @psalm-suppress MixedArgument
     */
    public function handle(...$args): Response
    {
        [$view, $data, $status, $headers] = array_slice($args, -4);

        /** @var string $view */
        /** @var array<string,scalar> $data */
        /** @var int $status */
        /** @var array<string,string> $headers */

        $response = $this->respond()->html(
            $this->creates_views->render($view, $data),
            $status
        );

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

}