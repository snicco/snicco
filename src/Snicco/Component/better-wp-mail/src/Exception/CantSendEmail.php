<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Exception;

use Throwable;

interface CantSendEmail extends Throwable
{

    public function getDebugData(): string;

}