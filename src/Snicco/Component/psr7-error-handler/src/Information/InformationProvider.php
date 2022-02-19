<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Information;

use Throwable;

interface InformationProvider
{

    public function createFor(Throwable $e): ExceptionInformation;

}