<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\ErrorHandler;

use ArrayIterator;
use IteratorAggregate;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;

use function array_unshift;

final class DisplayerCollection implements IteratorAggregate
{

    /**
     * @var ExceptionDisplayer[]
     */
    private array $displayers = [];

    public function append(ExceptionDisplayer $displayer): void
    {
        $this->displayers[] = $displayer;
    }

    public function prepend(ExceptionDisplayer $displayer): void
    {
        array_unshift($this->displayers, $displayer);
    }

    /**
     * @return ArrayIterator<array-key,ExceptionDisplayer>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->displayers);
    }
}