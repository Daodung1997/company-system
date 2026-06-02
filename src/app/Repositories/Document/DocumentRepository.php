<?php

namespace App\Repositories\Document;

use App\Models\Document;
use App\Repositories\Repository;

class DocumentRepository extends Repository
{
    public function __construct(Document $model)
    {
        parent::__construct($model);
    }
}
