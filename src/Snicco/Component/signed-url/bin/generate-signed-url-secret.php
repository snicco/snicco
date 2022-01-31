<?php

declare(strict_types=1);

use Snicco\Component\SignedUrl\Secret;

$possible_autoloader = [
    // monorepo
    dirname(__DIR__, 5) . '/vendor/autoload.php',
    // after split package
    dirname(__DIR__, 2) . '/vendor/autoload.php',
    // dependency
    dirname(__DIR__, 3) . '/vendor/autoload.php',
];

foreach ($possible_autoloader as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
}

echo "Store the following secret securely outside your webroot.\n";
echo "You should NEVER commit is secret into VCS.\n";
echo Secret::generate()->asString() . "\n";