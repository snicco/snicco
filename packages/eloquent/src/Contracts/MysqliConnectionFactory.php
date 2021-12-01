<?php

declare(strict_types=1);

namespace Snicco\Database\Contracts;

use Snicco\Database\MysqliConnection;

/**
 * @internal
 */
interface MysqliConnectionFactory
{
    
    public function create() :MysqliConnection;
    
}