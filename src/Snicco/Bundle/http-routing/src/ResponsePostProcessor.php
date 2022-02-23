<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting;

use Snicco\Component\EventDispatcher\Unremovable;
use Snicco\Component\Kernel\ValueObject\Environment;

/**
 * @psalm-external-mutation-free
 * @psalm-internal Snicco\Bundle\HttpRouting
 *
 * @interal
 */
final class ResponsePostProcessor implements Unremovable
{
    public bool $did_shutdown;
    private Environment $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
        $this->did_shutdown = false;
    }

    public function __invoke()
    {
        $this->did_shutdown = true;
        if ($this->environment->isTesting()) {
            return;
        }
        // @codeCoverageIgnoreStart
        exit();
        // @codeCoverageIgnoreEnd
    }
}