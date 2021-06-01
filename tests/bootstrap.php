<?php


    declare(strict_types = 1);

    use AdrianSuter\Autoload\Override\Override;
    use Tests\stubs\HeaderStack;
    use WPEmerge\Http\ResponseEmitter;

    $root_dir = getenv('ROOT_DIR');

    if ( ! defined('ROOT_DIR')) {

        define('ROOT_DIR', $root_dir);

    }

    if ( ! defined('VENDOR_DIR')) {

        define('VENDOR_DIR', $root_dir.DIRECTORY_SEPARATOR.'vendor');

    }

    if ( ! defined('DS')) {

        define('DS', DIRECTORY_SEPARATOR);

    }

    if ( ! defined('SITE_URL')) {

        define('SITE_URL', trim(getenv('SITE_URL'), '/'));

    }

    if ( ! defined('TESTS_DIR')) {

        define('TESTS_DIR', $root_dir.DS.'tests');

    }

    if ( ! defined('TESTS_CONFIG_PATH')) {

        define('TESTS_CONFIG_PATH', $root_dir.DS.'tests'.DS. 'stubs' . DS . 'test-app-config.php');

    }

    if ( ! defined('BLADE_CACHE')) {

        define('BLADE_CACHE', TESTS_DIR.DS.'integration'.DS.'Blade'.DS.'cache');

    }

    if ( ! defined('BLADE_VIEWS')) {

        define('BLADE_VIEWS', TESTS_DIR.DS.'integration'.DS.'Blade'.DS.'views');

    }

    if ( ! defined('ROUTES_DIR')) {

        define('ROUTES_DIR', TESTS_DIR.DS.'fixtures'.DS.'Routes');

    }

    if ( ! defined('VIEWS_DIR')) {

        define('VIEWS_DIR', TESTS_DIR.DS.'fixtures'.DS.'views');

    }

    if ( ! defined('TEST_CONFIG')) {

        $config = require_once TESTS_CONFIG_PATH;

        define('TEST_CONFIG', $config);

    }

    if ( ! defined('TEST_APP_KEY')) {

        define('TEST_APP_KEY', 'base64:LOK1UydvZ50A9iyTC2KxuP/C6k8TAM4UlGDcjwsKQik=');

    }

    if ( ! defined('WPEMERGE_RUNNING_UNIT_TESTS')) {

        define('WPEMERGE_RUNNING_UNIT_TESTS', true);

    }



    $classLoader = require $root_dir.DS.'vendor'.DS.'autoload.php';

    Override::apply($classLoader, [

        ResponseEmitter::class => [

            'connection_status' => function () : int {

                if (isset($GLOBALS['connection_status_return'])) {
                    return $GLOBALS['connection_status_return'];
                }

                return connection_status();
            },

            'header' => function (string $string, bool $replace = true, int $statusCode = null) : void {

                HeaderStack::push(
                    [
                        'header' => $string,
                        'replace' => $replace,
                        'status_code' => $statusCode,
                    ]
                );
            },

            'headers_sent' => function () : bool {

                return false;

            },
        ],
    ]);
