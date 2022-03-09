<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPCap;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\Psr7ErrorHandler\HttpException;

use function sprintf;

final class Authorize implements MiddlewareInterface
{
    private string $capability;

    private ?int $object_id;

    private BetterWPAPI $wp;

    public function __construct(string $capability, int $object_id = null, BetterWPAPI $wp = null)
    {
        $this->wp = $wp ?: new BetterWPAPI();
        $this->capability = $capability;
        $this->object_id = $object_id;
    }

    /**
     * @throws HttpException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $args = [];
        if ($this->object_id) {
            $args[] = $this->object_id;
        }

        if ($this->wp->currentUserCan($this->capability, ...$args)) {
            return $handler->handle($request);
        }

        throw new HttpException(
            403,
            sprintf(
                'Authorization failed for path [%s] with required capability [%s].',
                $request->getUri()->getPath(),
                $this->capability
            )
        );
    }
}
