<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use LogicException;
use Throwable;

final class SessionIsLocked extends LogicException
{
    public function __construct(
        string $message = 'The session has been persisted and can not be changed any longer.',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
}
