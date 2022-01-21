<?php

declare(strict_types=1);

foreach ([
    'SECONDARY_DB_NAME',
] as $param) {
    if ( ! isset($_SERVER[$param])) {
        echo "The eloquent test suite needs the env parameter [$param].";
        die(1);
    }
}
