<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Exception;

/**
 * @api
 */
interface CantSendEmail
{
    
    public function getDebugData() :string;
    
}