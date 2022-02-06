<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Exception;

use Throwable;

/**
 * @api
 */
interface CantSendEmail extends Throwable
{

    public function getDebugData(): string;

}