<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Response;

use Snicco\Component\HttpRouting\Http\Psr7\Response;

final class DelegatedResponse extends Response
{
    private bool $should_sent_headers = true;

    public function shouldHeadersBeSent(): bool
    {
        return $this->should_sent_headers;
    }

    public function withoutSendingHeaders(): DelegatedResponse
    {
        $new = clone $this;
        $new->should_sent_headers = false;

        return $new;
    }
}
