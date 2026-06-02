<?php

namespace App\Observers;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Models\Job;
use App\Models\WorkerProfile;

class JobObserver
{
    /**
     * Handle the Job "updated" event.
     */
    public function updated(Job $job): void
    {
        if ($job->isDirty('status')) {
            $this->updateWorkerJobStats($job->worker_id);
        }
    }

    private function updateWorkerJobStats($workerId)
    {
        if (! $workerId) {
            return;
        }

        $workerProfile = WorkerProfile::where('user_id', $workerId)->first();
        if (! $workerProfile) {
            return;
        }

        // Count Completed Jobs
        $completedCount = Job::where('worker_id', $workerId)
            ->where('status', JobStatusConst::COMPLETED)
            ->count();

        // Count Cancelled Jobs (where worker is assigned)
        $cancelledCount = Job::where('worker_id', $workerId)
            ->whereIn('status', [JobStatusConst::CANCELLED, JobStatusConst::REFUNDED, JobStatusConst::COMPLAINT])
            ->count();

        $workerProfile->update([
            'total_completed_jobs' => $completedCount,
            'total_cancelled_jobs' => $cancelledCount,
        ]);
    }
}
