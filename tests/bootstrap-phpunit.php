<?php

declare(strict_types=1);

if ( ! isset($_ENV['DB_USER'])) {
    throw new RuntimeException("DB_USER not set in environment");
}
if ( ! isset($_ENV['DB_NAME'])) {
    throw new RuntimeException("DB_NAME not set in environment");
}

if ( ! isset($_ENV['DB_PASSWORD'])) {
    throw new RuntimeException("DB_PASSWORD not set in environment");
}

if ( ! isset($_ENV['DB_HOST'])) {
    throw new RuntimeException("DB_HOST not set in environment");
}

require_once dirname(__DIR__, 1).'/vendor/autoload.php';

