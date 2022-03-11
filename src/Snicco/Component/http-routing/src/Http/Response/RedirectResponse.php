<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Response;

use Snicco\Component\HttpRouting\Http\Psr7\Response;

final class RedirectResponse extends Response
{
    private bool $bypass_validation = false;

    public function to(string $url): RedirectResponse
    {
        return $this->withHeader('Location', $url);
    }

    public function isExternalRedirectAllowed(): bool
    {
        return $this->bypass_validation;
    }

    public function withExternalRedirectAllowed(): RedirectResponse
    {
        $res = clone $this;
        $res->bypass_validation = true;

        return $res;
    }
}
