<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Information;

use Throwable;

final class ExceptionInformation
{

    private int $status_code;
    private string $identifier;
    private string $title;
    private string $safe_details;
    private Throwable $original;
    private Throwable $transformed;

    public function __construct(
        int $status_code,
        string $identifier,
        string $title,
        string $safe_details,
        Throwable $original,
        Throwable $transformed
    ) {
        $this->status_code = $status_code;
        $this->identifier = $identifier;
        $this->title = $title;
        $this->original = $original;
        $this->safe_details = $safe_details;
        $this->transformed = $transformed;
    }

    public function statusCode(): int
    {
        return $this->status_code;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function title(): string
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

}