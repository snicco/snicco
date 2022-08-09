<?php

declare(strict_types=1);

if (! function_exists('snicco_get_exception')) {
    function snicco_get_exception(string $message): LogicException
    {
        return new LogicException($message);
    }
}
