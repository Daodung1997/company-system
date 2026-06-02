<?php

namespace App\Repositories\JobInvitedWorker;

use App\Models\JobInvitedWorker;
use App\Repositories\Repository;

class JobInvitedWorkerRepository extends Repository
{
    public function __construct(JobInvitedWorker $model)
    {
        parent::__construct($model);
    }

    public function getInvitedWorkerIds($jobId)
    {
        return $this->model->where('job_id', $jobId)
            ->where('status', JobInvitedWorker::STATUS_ASSIGNED)
            ->pluck('worker_id')
            ->toArray();
    }

    public function isWorkerInvited($jobId, $workerId)
    {
        return $this->model->where('job_id', $jobId)
            ->where('worker_id', $workerId)
            ->where('status', JobInvitedWorker::STATUS_ASSIGNED)
            ->exists();
    }

    public function hasInvitedWorkers($jobId)
    {
        return $this->model->where('job_id', $jobId)->exists();
    }

    public function insertInvitations(array $invitations)
    {
        return $this->model->insert($invitations);
    }
}
