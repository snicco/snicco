<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\fixtures;

use Snicco\Component\BetterWPDB\QueryInfo;
use Snicco\Component\BetterWPDB\QueryLogger;

final class TestLogger implements QueryLogger
{
    /**
     * @var QueryInfo[]
     */
    public array $queries = [];

    public function log(QueryInfo $info): void
    {
        $this->queries[] = $info;
    }
}
