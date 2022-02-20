<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\ErrorHandler;

use Snicco\Component\Psr7ErrorHandler\Log\RequestContext;

use function array_unique;
use function array_unshift;
use function array_values;

final class RequestLogContextCollection
{
    /**
     * @var RequestContext[]
     */
    private array $transformers = [];

    public function append(RequestContext $displayer): void
    {
        $this->transformers[] = $displayer;
    }

    public function prepend(RequestContext $displayer): void
    {
        array_unshift($this->transformers, $displayer);
    }

    /**
     * @return list<RequestContext>
     */
    public function all(): array
    {
        return array_values(array_unique($this->transformers));
    }
}