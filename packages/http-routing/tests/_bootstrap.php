<?php

use AdrianSuter\Autoload\Override\Override;
use Tests\HttpRouting\fixtures\HeaderStack;
use Snicco\HttpRouting\Http\ResponseEmitter;
use Snicco\HttpRouting\Http\ResponsePreparation;

$file = dirname(__DIR__, 3).'/codeception/bootstrap-global.php';

require_once $file;

$autoload = require REPOSITORY_ROOT_DIR.DS.'vendor'.DS.'autoload.php';

Override::apply($autoload, [
    
    ResponseEmitter::class => [
        
        'connection_status' => function () :int {
            if (isset($GLOBALS['connection_status_return'])) {
                return $GLOBALS['connection_status_return'];
            }
            
            return connection_status();
        },
        'header' => function (string $string, bool $replace = true, int $statusCode = null) :void {
            HeaderStack::push(
                [
                    'header' => $string,
                    'replace' => $replace,
                    'status_code' => $statusCode,
                ]
            );
        },
        'headers_sent' => function () :bool {
            return false;
        },
    ],
    
    ResponsePreparation::class => [
        
        'headers_list' => function () {
            $headers = [];
            
            foreach (HeaderStack::stack() as $header) {
                $headers[] = $header['header'] ?? null;
            }
            
            return array_filter($headers);
        },
    
    ],

]);