<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing\Tests\fixtures;

use Psr\Http\Message\UploadedFileInterface;
use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

use function strpos;

final class WebTestCaseController extends Controller
{
    public function __invoke(): Response
    {
        return $this->respondWith()->html('<h1>' . __CLASS__ . '</h1>');
    }

    public function queryParams(Request $request): Response
    {
        return $this->respondWith()->json($request->getQueryParams());
    }

    public function cookiesAsJson(Request $request): Response
    {
        return $this->respondWith()->json($request->getCookieParams());
    }

    public function bodyAsJson(Request $request): Response
    {
        return $this->respondWith()->json($request->getParsedBody());
    }

    public function filesAsJson(Request $request): Response
    {
        $info = [];

        /**
         * @var UploadedFileInterface $file
         */
        foreach ($request->getUploadedFiles() as $file) {
            $info[] = [
                'size' => $file->getSize(),
                'name' => $file->getClientFilename()
            ];
        }
        return $this->respondWith()->json($info);
    }

    public function admin(): Response
    {
        return $this->respondWith()->html('admin');
    }

    public function checkIfApi(Request $request): Response
    {
        return $this->respondWith()->html($request->isToApiEndpoint() ? 'true' : 'false');
    }

    public function serverVars(Request $request): Response
    {
        $string = '';

        /**
         * @var string $value
         */
        foreach ($request->getServerParams() as $name => $value) {
            if (strpos((string)$name, 'X-') === 0) {
                $string .= "$name=$value";
            }
        }
        return $this->respondWith()->html($string);
    }

    public function fullUrl(Request $request): Response
    {
        return $this->respondWith()->html($request->fullUrl());
    }

}