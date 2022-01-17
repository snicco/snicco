<?php

declare(strict_types=1);

namespace Tests\Database\helpers;

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