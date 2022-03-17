<?php

declare(strict_types=1);

if (! isset($_SERVER['WP_ROOT_FOLDER']) || ! is_string($_SERVER['WP_ROOT_FOLDER'])) {
    echo '$_SERVER[WP_ROOT_FOLDER] is not set.';
    exit(1);
}

if (! is_file($_SERVER['WP_ROOT_FOLDER'] . '/wp-content/object-cache.php')) {
    echo sprintf('A persistent wp-object cache drop-in is needed to run the tests for [%s].', dirname(__DIR__));
    exit(1);
}
