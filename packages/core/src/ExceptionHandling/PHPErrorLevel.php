<?php

namespace Snicco\ExceptionHandling;

use Psr\Log\LogLevel;

class PHPErrorLevel
{
    
    public static function toPsr3(int $PHP_ERROR_LEVEL) :string
    {
        switch ($PHP_ERROR_LEVEL) {
            case E_STRICT:
            case E_NOTICE:
            case E_USER_NOTICE:
                return LogLevel::NOTICE;
            case E_WARNING:
            case E_USER_WARNING:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return LogLevel::WARNING;
            case E_ERROR:
            case E_RECOVERABLE_ERROR:
                return LogLevel::ERROR;
            default:
                return LogLevel::CRITICAL;
        }
    }
    
}