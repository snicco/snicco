<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB;

interface QueryLogger
{
    public function log(QueryInfo $info): void;
}