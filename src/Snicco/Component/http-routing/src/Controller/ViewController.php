<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Snicco\Component\HttpRouting\Exception\CouldNotRenderTemplate;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Renderer\TemplateRenderer;

use function array_slice;

/**
 * @interal
 */
final class ViewController extends Controller
{

    private TemplateRenderer $renderer;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @param mixed ...$args
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws CouldNotRenderTemplate
     */
    public function __invoke(...$args): Response
    {
        [$view, $data, $status, $headers] = array_slice($args, -4);

        /** @psalm-suppress  MixedArgument */
        $response = $this->respond()->html(
            $this->renderer->render($view, $data),
            $status
        );

        /**
         * @var array<string,string> $headers
         */
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

}