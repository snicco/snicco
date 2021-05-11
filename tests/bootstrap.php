<?php


	declare( strict_types = 1 );

	$root_dir = getenv('ROOT_DIR');


	if ( ! defined('DS') ) {

		define( 'DS',  DIRECTORY_SEPARATOR );

	}

	if ( ! defined('SITE_URL') ) {

		define( 'SITE_URL',  getenv('SITE_URL') );

	}


	if ( ! defined('TESTS_DIR') ) {

		define('TESTS_DIR', $root_dir . DS . 'tests');

	}

	if ( ! defined('TESTS_CONFIG_PATH') ) {

		define('TESTS_CONFIG_PATH', $root_dir . DS . 'tests' . DS . 'test-config.php' );

	}



    if ( ! defined('TEST_CONFIG') ) {

    	$config = require_once TESTS_CONFIG_PATH;

		define('TEST_CONFIG', $config );

	}




	require_once $root_dir . DS . 'vendor' . DS . 'autoload.php';
