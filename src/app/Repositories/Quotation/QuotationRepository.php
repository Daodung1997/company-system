<?php

namespace App\Repositories\Quotation;

use App\Models\Quotation;
use App\Repositories\Repository;

class QuotationRepository extends Repository implements QuotationRepositoryInterface
{
    public function __construct(Quotation $model)
    {
        parent::__construct($model);
    }

    public function findByJobAndWorker($jobId, $workerId)
    {
        return $this->model
            ->where('job_id', $jobId)
            ->where('worker_id', $workerId)
            ->first();
    }

    public function get($columns = ['*'])
    {
        return $this->all($columns);
    }

    public function getQuotedJobIdsByWorker($workerId): array
    {
        return $this->model
            ->where('worker_id', $workerId)
            ->pluck('job_id')
            ->toArray();
    }

    public function rejectOtherQuotations($jobId, $acceptedQuotationId)
    {
        return $this->model
            ->where('job_id', $jobId)
            ->where('id', '!=', $acceptedQuotationId)
            ->update(['status' => \App\Constants\Master\Models\Quotation\QuotationStatusConst::REJECTED]);
    }
}
