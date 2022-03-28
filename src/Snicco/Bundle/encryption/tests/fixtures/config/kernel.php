<?php

declare(strict_types=1);

use Snicco\Bundle\Encryption\EncryptionBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    'bundles' => [
        Environment::ALL => [EncryptionBundle::class],
    ],
];
