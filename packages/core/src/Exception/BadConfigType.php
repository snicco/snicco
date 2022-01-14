<?php

declare(strict_types=1);

namespace Snicco\Core\Exception;

use LogicException;

final class BadConfigType extends LogicException
{
    
    public static function forKey(string $key, string $expected_type, string $actual_type) :self
    {
        return new self(
            "Expected config value for key [$key] to be [$expected_type].\nGot [$actual_type]."
        );
    }
    
}