<?php

declare(strict_types=1);

error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$root_dir = getenv('WP_ROOT_FOLDER').DIRECTORY_SEPARATOR.'framework';
$tests_dir = $root_dir.DIRECTORY_SEPARATOR.'tests';

if ( ! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if ( ! defined('ROOT_DIR')) {
    define('ROOT_DIR', $root_dir);
}

if ( ! defined('TESTS_DIR')) {
    define('TESTS_DIR', $root_dir.DS.'tests');
}

if ( ! defined('TEST_APP_KEY')) {
    define('TEST_APP_KEY', 'base64:LOK1UydvZ50A9iyTC2KxuP/C6k8TAM4UlGDcjwsKQik=');
}

if ( ! defined('FLUSHABLE_SITE_WP_URL')) {
    define('SITE_URL', trim(getenv('FLUSHABLE_SITE_WP_URL'), '/'));
}

if ( ! defined('FIXTURES_DIR')) {
    define('FIXTURES_DIR', $tests_dir.DS.'fixtures');
}

if ( ! defined('ROUTES_DIR')) {
    define('ROUTES_DIR', FIXTURES_DIR.DS.'routes');
}

if ( ! defined('BLADE_VIEWS')) {
    define('BLADE_VIEWS', $tests_dir.DS.'integration'.DS.'Blade'.DS.'views');
}

if ( ! defined('VIEWS_DIR')) {
    define('VIEWS_DIR', $tests_dir.DS.'fixtures'.DS.'views');
}

if ( ! defined('BLADE_CACHE')) {
    define('BLADE_CACHE', $tests_dir.DS.'integration'.DS.'Blade'.DS.'cache');
}

//if ( ! defined('VENDOR_DIR')) {
//    define('VENDOR_DIR', $root_dir.DIRECTORY_SEPARATOR.'vendor');
//}
//

//

//
//if ( ! defined('TESTS_DIR')) {
//    define('TESTS_DIR', $root_dir.DS.'tests');
//}
//
//if ( ! defined('TESTS_CONFIG_PATH')) {
//    define('TESTS_CONFIG_PATH', $root_dir.DS.'tests'.DS.'stubs'.DS.'test-app-config.php');
//}
//

//

//
//if ( ! defined('TEST_CONFIG')) {
//    $config = require TESTS_CONFIG_PATH;
//
//    define('TEST_CONFIG', $config);
//}

require $root_dir.DS.'vendor'.DS.'autoload.php';


