<?php

declare(strict_types=1);

namespace Tests\integration\Database;

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