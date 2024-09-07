<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\ResponseUtils;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

use Webmozart\Assert\Assert;

use function sprintf;

abstract class Controller
{
    private ContainerInterface $container;

    private ?Request $current_request = null;

    /**
     * @return iterable<ControllerMiddleware>
     */
    public static function middleware()
    {
        return [];
    }

    /**
     * @psalm-internal Snicco\Component\HttpRouting
     */
    final public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @psalm-internal Snicco\Component\HttpRouting
     */
    final public function setCurrentRequest(Request $request): void
    {
        $this->current_request = $request;
    }

    final protected function url(): UrlGenerator
    {
        try {
            $url = $this->container->get(UrlGenerator::class);
            Assert::isInstanceOf($url, UrlGenerator::class);

            return $url;
        } catch (ContainerExceptionInterface $e) {
            throw new LogicException(
                "The UrlGenerator is not bound correctly in the psr container.\nMessage: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    final protected function responseFactory(): ResponseFactory
    {
        try {
            $res = $this->container->get(ResponseFactory::class);
            Assert::isInstanceOf($res, ResponseFactory::class);

            return $res;
        } catch (ContainerExceptionInterface $e) {
            throw new LogicException(
                "The ResponseFactory is not bound correctly in the psr container.\nMessage: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    final protected function respondWith(): ResponseUtils
    {
        return new ResponseUtils($this->url(), $this->responseFactory(), $this->currentRequest());
    }

    private function currentRequest(): Request
    {
        if (! isset($this->current_request)) {
            throw new RuntimeException(sprintf('Current request not set on controller [%s]', static::class));
        }

        return $this->current_request;
    }
}
