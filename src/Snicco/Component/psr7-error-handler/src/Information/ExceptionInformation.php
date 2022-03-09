<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Information;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ExceptionInformation
{
    private int $status_code;

    private string $identifier;

    private string $title;

    private string $safe_details;

    private Throwable $original;

    private Throwable $transformed;

    private ServerRequestInterface $server_request;

    public function __construct(
        int $status_code,
        string $identifier,
        string $title,
        string $safe_details,
        Throwable $original,
        Throwable $transformed,
        ServerRequestInterface $request
    ) {
        $this->status_code = $status_code;
        $this->identifier = $identifier;
        $this->title = $title;
        $this->original = $original;
        $this->safe_details = $safe_details;
        $this->transformed = $transformed;
        $this->server_request = $request;
    }

    public function statusCode(): int
    {
        return $this->status_code;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function safeTitle(): string
    {
        return $this->title;
    }

    public function originalException(): Throwable
    {
        return $this->original;
    }

    public function transformedException(): Throwable
    {
        return $this->transformed;
    }

    public function safeDetails(): string
    {
        return $this->safe_details;
    }

    public function serverRequest(): ServerRequestInterface
    {
        return $this->server_request;
    }
}
