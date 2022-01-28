<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Displayer;

use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

/**
 * @api
 */
interface ExceptionDisplayer
{
    
    public function display(ExceptionInformation $exception_information) :string;
    
    public function supportedContentType() :string;
    
    public function isVerbose() :bool;
    
    public function canDisplay(ExceptionInformation $exception_information) :bool;
    
}