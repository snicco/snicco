<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    private int $status_code;

    /**
     * @var array<string,string>
     */
    private array $response_headers;

    /**
     * @param array<string,string> $response_headers
     */
    public function __construct(
        int $status_code,
        string $message,
        array $response_headers = [],
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->status_code = $status_code;
        $this->response_headers = $response_headers;
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }

    /**
     * @param array<string,string> $response_headers
     */
    final public static function fromPrevious(int $status_code, Throwable $previous, array $response_headers = []): self
    {
        return new self(
            $status_code,
            $previous->getMessage(),
            $response_headers,
            (int) $previous->getCode(),
            $previous
        );
    }

    /**
     * @return array<string,string>
     */
    final public function headers(): array
    {
        return $this->response_headers;
    }

    final public function statusCode(): int
    {
        return $this->status_code;
    }
}
