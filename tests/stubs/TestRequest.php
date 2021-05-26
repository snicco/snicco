<?php


    declare(strict_types = 1);


    namespace Tests\stubs;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Tests\helpers\CreatePsr17Factories;
    use WPEmerge\Http\Psr7\Request;

    class TestRequest extends Request
    {

        use CreatePsr17Factories;

        public $body;

        public static function fromFullUrl(string $method, string $url) : Request
        {

            $psr17Factory = new Psr17Factory();

            return new Request($psr17Factory->createServerRequest($method, $url));

        }

        public static function from(string $method, $path, $host = null) : Request
        {

            $psr17Factory = new Psr17Factory();

            $path = ltrim($path, '/');
            $method = strtoupper($method);

            $host = $host ?? 'https://foo.com';
            $url = trim($host, '/').'/'.$path;

            return new Request($psr17Factory->createServerRequest($method, $url));


        }


    }