<?php

namespace Snicco\Component\HttpRouting\Http\Response;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

/**
 * @psalm-suppress InvalidExtendClass
 */
final class DelegatedResponse extends Response
{

    private bool $should_sent_headers;

    /**
     * @psalm-suppress MethodSignatureMismatch
     * @psalm-suppress ImplementedParamTypeMismatch
     * @psalm-suppress ConstructorSignatureMismatch
     */
    public function __construct(bool $should_sent_headers, ResponseInterface $psr_response)
    {
        parent::__construct($psr_response);
        $this->should_sent_headers = $should_sent_headers;
    }

    public function shouldHeadersBeSent(): bool
    {
        return $this->should_sent_headers;
    }

}