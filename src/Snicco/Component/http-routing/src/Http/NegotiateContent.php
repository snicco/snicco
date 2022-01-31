<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use Middlewares\ContentLanguage;
use Middlewares\ContentType;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Webmozart\Assert\Assert;

final class NegotiateContent extends AbstractMiddleware
{

    private array $content_types;

    /**
     * @var string[]
     */
    private array $languages;

    /**
     * @var string[]
     */
    private array $charsets;

    public function __construct(array $languages, array $content_types = null, array $charsets = null)
    {
        Assert::allString($languages);
        $this->languages = $languages;
        $this->charsets = $charsets ?: ['UTF-8'];
        $this->content_types = $content_types ?: $this->defaultConfiguration();
    }

    private function defaultConfiguration(): array
    {
        return [
            'html' => [
                'extension' => ['html', 'php'],
                'mime-type' => ['text/html'],
                'charset' => true,
            ],
            'txt' => [
                'extension' => ['txt'],
                'mime-type' => ['text/plain'],
                'charset' => true,
            ],
            'json' => [
                'extension' => ['json'],
                'mime-type' => ['application/json'],
                'charset' => true,
            ],
        ];
    }

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $content_type = new ContentType($this->content_types);
        $content_type->charsets($this->charsets);
        $content_type->errorResponse($this->respond());
        $content_type->nosniff(true);
        $language = new ContentLanguage($this->languages);

        $response = $content_type->process($request, $this->next($language, $next));

        if (406 === $response->getStatusCode()) {
            throw new HttpException(
                406, "Failed content negotiation for path [{$request->path()}]."
            );
        }
        return $response;
    }

    private function next(ContentLanguage $language, NextMiddleware $next): NextMiddleware
    {
        return new NextMiddleware(function (Request $request) use ($language, $next) {
            return $language->process($request, $next);
        });
    }

}