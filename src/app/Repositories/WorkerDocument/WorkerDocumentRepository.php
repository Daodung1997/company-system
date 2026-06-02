<?php

namespace App\Repositories\WorkerDocument;

use App\Models\WorkerDocument;
use App\Repositories\Repository;

class WorkerDocumentRepository extends Repository
{
    public function __construct(WorkerDocument $model)
    {
        parent::__construct($model);
    }
}
