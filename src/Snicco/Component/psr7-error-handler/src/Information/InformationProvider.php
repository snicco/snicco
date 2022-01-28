<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Information;

use Throwable;

/**
 * @api
 */
interface InformationProvider
{
    
    public function provideFor(Throwable $e) :ExceptionInformation;
    
}