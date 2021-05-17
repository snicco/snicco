<?php


    declare(strict_types = 1);

    $root_dir = getenv('ROOT_DIR');

    if ( ! defined('ROOT_DIR')) {

        define('ROOT_DIR', $root_dir);

    }

    if ( ! defined('VENDOR_DIR') ) {

        define('VENDOR_DIR', $root_dir . DIRECTORY_SEPARATOR . 'vendor');

    }

    if ( ! defined('DS')) {

        define('DS', DIRECTORY_SEPARATOR);

    }

    if ( ! defined('SITE_URL')) {

        define('SITE_URL', getenv('SITE_URL'));

    }

    if ( ! defined('TESTS_DIR')) {

        define('TESTS_DIR', $root_dir.DS.'tests');

    }

    if ( ! defined('TESTS_CONFIG_PATH')) {

        define('TESTS_CONFIG_PATH', $root_dir.DS.'tests'.DS.'test-config.php');

    }

    if ( ! defined('TEST_CONFIG')) {

        $config = require_once TESTS_CONFIG_PATH;

        define('TEST_CONFIG', $config);

    }

    if ( ! defined('BLADE_CACHE')) {

        define('BLADE_CACHE', TESTS_DIR . DS . 'integration' . DS . 'Blade' . DS . 'cache');

    }

     if ( ! defined('BLADE_VIEWS')) {

        define('BLADE_VIEWS', TESTS_DIR . DS . 'integration' . DS . 'Blade' . DS . 'views');

    }



    require_once $root_dir.DS.'vendor'.DS.'autoload.php';
