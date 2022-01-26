<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler;

interface Displayer
{
    
    public function display(HttpException $e, string $identifier) :string;
    
    public function supportedContentType() :string;
    
    public function isVerbose() :bool;
    
    public function canDisplay(HttpException $e) :bool;
    
}