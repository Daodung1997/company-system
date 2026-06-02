<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Class AbstractService
 */
abstract class AbstractService
{
    protected function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    protected function commitTransaction(): void
    {
        DB::commit();
    }

    protected function rollbackTransaction(): void
    {
        DB::rollBack();
    }
}
