<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\GarbageCollector;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;

final class CollectGarbage implements MiddlewareInterface
{

    private int $percentage;
    private LoggerInterface $logger;
    private SignedUrlStorage $storage;

    public function __construct(int $percentage, SignedUrlStorage $storage, LoggerInterface $logger)
    {
        $this->percentage = $percentage;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            GarbageCollector::clean($this->storage, $this->percentage);
        } catch (UnavailableStorage $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return $handler->handle($request);
    }

}