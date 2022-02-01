<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPCap;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\ScopableWP\ScopableWP;

use function sprintf;

/**
 * @api
 */
final class Authorize extends AbstractMiddleware
{

    private string $capability;
    private ?int $object_id;
    private ScopableWP $wp;

    public function __construct(ScopableWP $wp, $capability, int $object_id = null)
    {
        $this->wp = $wp;
        $this->capability = $capability;
        $this->object_id = $object_id;
    }

    /**
     * @throws HttpException
     */
    public function handle(Request $request, $next): ResponseInterface
    {
        $args = [];
        if ($this->object_id) {
            $args[] = $this->object_id;
        }

        if ($this->wp->currentUserCan($this->capability, ...$args)) {
            return $next($request);
        }

        throw new HttpException(
            403,
            sprintf(
                'Authorization failed for path [%s] with required capability [%s].',
                $request->path(),
                $this->capability
            )
        );
    }

}
