<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Contracts;

interface TransportException
{
    
    public function getDebugData() :string;
    
}