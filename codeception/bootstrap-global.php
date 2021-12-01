<?php

declare(strict_types=1);

if ( ! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

$root_dir = getenv('WP_FOLDER').DIRECTORY_SEPARATOR.'framework';
$repository_root_dir = getenv('REPOSITORY_ROOT_DIR');

if ( ! defined('REPOSITORY_ROOT_DIR')) {
    define('REPOSITORY_ROOT_DIR', $repository_root_dir);
}

if ( ! defined('CODECEPTION_DIR')) {
    define('CODECEPTION_DIR', $repository_root_dir.DS.'codeception');
}

if ( ! defined('SHARED_FIXTURES_DIR')) {
    define('SHARED_FIXTURES_DIR', CODECEPTION_DIR.DS.'shared'.DS.'fixtures');
}

if ( ! defined('PACKAGES_DIR')) {
    define('PACKAGES_DIR', $root_dir.DS.'packages');
}

if ( ! defined('SITE_URL')) {
    define('SITE_URL', trim(getenv('WP_SITE_URL'), '/'));
}

if ( ! defined('TEST_APP_KEY')) {
    define('TEST_APP_KEY', 'base64:LOK1UydvZ50A9iyTC2KxuP/C6k8TAM4UlGDcjwsKQik=');
}



