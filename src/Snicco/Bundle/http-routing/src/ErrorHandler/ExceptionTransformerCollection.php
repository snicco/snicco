<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\ErrorHandler;

use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;

use function array_unique;
use function array_unshift;
use function array_values;

final class ExceptionTransformerCollection
{

    /**
     * @var ExceptionTransformer[]
     */
    private array $transformers = [];

    public function append(ExceptionTransformer $displayer): void
    {
        $this->transformers[] = $displayer;
    }

    public function prepend(ExceptionTransformer $displayer): void
    {
        array_unshift($this->transformers, $displayer);
    }

    /**
     * @return list<ExceptionTransformer>
     */
    public function all(): array
    {
        return array_values(array_unique($this->transformers));
    }

}