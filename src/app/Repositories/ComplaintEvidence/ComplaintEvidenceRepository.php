<?php

namespace App\Repositories\ComplaintEvidence;

use App\Models\ComplaintEvidence;
use App\Repositories\Repository;

class ComplaintEvidenceRepository extends Repository implements ComplaintEvidenceRepositoryInterface
{
    public function __construct(ComplaintEvidence $model)
    {
        parent::__construct($model);
    }
}
