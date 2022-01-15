<?php

if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
    $autoload = require_once dirname(__DIR__).'/vendor/autoload.php';
}
else {
    $autoload = dirname(__DIR__, 4);
}

//Override::apply($autoload, [
//
//    ResponseEmitter::class => [
//
//        'connection_status' => function () :int {
//            if (isset($GLOBALS['connection_status_return'])) {
//                return $GLOBALS['connection_status_return'];
//            }
//
//            return connection_status();
//        },
//        'header' => function (string $string, bool $replace = true, int $statusCode = null) :void {
//            HeaderStack::push(
//                [
//                    'header' => $string,
//                    'replace' => $replace,
//                    'status_code' => $statusCode,
//                ]
//            );
//        },
//        'headers_sent' => function () :bool {
//            return false;
//        },
//    ],
//
//    ResponsePreparation::class => [
//
//        'headers_list' => function () {
//            $headers = [];
//
//            foreach (HeaderStack::stack() as $header) {
//                $headers[] = $header['header'] ?? null;
//            }
//
//            return array_filter($headers);
//        },
//
//    ],
//
//]);