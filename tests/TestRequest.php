<?php


    declare(strict_types = 1);


    namespace Tests;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use WPEmerge\Http\Request;

    class TestRequest extends Request
    {

        public $body;

        public static function fromFullUrl(string $method, string $url) : Request
        {

            $psr17Factory = new Psr17Factory();

            return new Request($psr17Factory->createServerRequest($method, $url));

        }

        public static function from(string $method, $path, $host = null) : Request
        {

            $psr17Factory = new Psr17Factory();

            $path = trim($path, '/') ? : '/';
            $method = strtoupper($method);

            $host = $host ?? 'https://foo.com';
            $url = trim($host, '/').'/'.$path;
            $url = trim($url, '/').'/';

            return new Request($psr17Factory->createServerRequest($method, $url));


        }


    }