<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Helper;

use Illuminate\Support\Facades\DB;

trait WithTestTransactions
{
    
    protected function beginTransaction()
    {
        DB::beginTransaction();
    }
    
    protected function rollbackTransaction()
    {
        DB::rollBack();
    }
    
}