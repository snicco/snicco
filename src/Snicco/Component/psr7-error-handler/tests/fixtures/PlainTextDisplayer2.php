<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\fixtures;

use Snicco\Component\Psr7ErrorHandler\Displayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

final class PlainTextDisplayer2 implements Displayer
{
    
    public function display(ExceptionInformation $exception_information) :string
    {
        return 'plain_text2';
    }
    
    public function supportedContentType() :string
    {
        return 'text/plain';
    }
    
    public function isVerbose() :bool
    {
        return false;
    }
    
    public function canDisplay(ExceptionInformation $exception_information) :bool
    {
        return true;
    }
    
}