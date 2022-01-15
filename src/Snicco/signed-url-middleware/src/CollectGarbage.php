<?php

declare(strict_types=1);

namespace Snicco\SignedUrlMiddleware;

use RuntimeException;
use Psr\Log\LoggerInterface;
use Snicco\SignedUrl\GarbageCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;

final class CollectGarbage implements MiddlewareInterface
{
    
    /**
     * @var int
     */
    private $percentage;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SignedUrlStorage
     */
    private $storage;
    
    public function __construct(int $percentage, SignedUrlStorage $storage, LoggerInterface $logger)
    {
        $this->percentage = $percentage;
        $this->storage = $storage;
        $this->logger = $logger;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        try {
            GarbageCollector::clean($this->storage, $this->percentage);
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
        
        return $handler->handle($request);
    }
    
}