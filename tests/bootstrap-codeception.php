<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$dot_env = Dotenv::createImmutable(__DIR__, '.env.testing');
$dot_env->load();
$dot_env->required(['WP_ROOT_FOLDER', 'DB_NAME', 'DB_PREFIX', 'DB_USER', 'DB_HOST', 'DB_PASSWORD']);

require_once dirname(__DIR__, 1).'/vendor/autoload.php';

