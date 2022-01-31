<?php

declare(strict_types=1);

if (!file_exists($_SERVER['WP_ROOT_FOLDER'] . '/wp-content/object-cache.php')) {
    echo sprintf(
        'A persistent wp-object cache drop-in is needed to run the tests for [%s].',
        dirname(__DIR__)
    );
    exit(1);
}