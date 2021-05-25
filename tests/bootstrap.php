<?php


    declare(strict_types = 1);

    use AdrianSuter\Autoload\Override\Override;
    use Tests\HeaderStack;
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

        define('BLADE_CACHE', TESTS_DIR.DS.'integration'.DS.'Blade'.DS.'cache');

    }

    if ( ! defined('BLADE_VIEWS')) {

        define('BLADE_VIEWS', TESTS_DIR.DS.'integration'.DS.'Blade'.DS.'views');

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
