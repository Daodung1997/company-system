<?php

namespace App\Services\Worker\Home;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Repositories\Job\JobRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\WorkerProfile\WorkerProfileRepository;
use App\Services\AbstractService;

class HomeService extends AbstractService
{
    public function __construct(
        protected JobRepository $jobRepository,
        protected UserRepository $userRepository,
        protected WorkerProfileRepository $workerProfileRepository
    ) {}

    public function getHome(int $userId): array
    {
        $worker = $this->userRepository->find($userId);

        // This is a simplified approach, ideally moved to Repository
        $summary = [
            'active_jobs' => $this->jobRepository->getInstance()
                ->where('worker_id', $userId)
                ->whereIn('status', [JobStatusConst::IN_PROGRESS, JobStatusConst::PENDING_PAYMENT])
                ->count(),
            'pending_quotes' => 0, // Assuming t_quotes table isn't fully mocked here, placeholder
            'in_progress' => $this->jobRepository->getInstance()
                ->where('worker_id', $userId)
                ->where('status', JobStatusConst::IN_PROGRESS)
                ->count(),
            'completed' => $this->jobRepository->getInstance()
                ->where('worker_id', $userId)
                ->where('status', JobStatusConst::COMPLETED)
                ->count(),
        ];

        // Suggested jobs
        $suggestedJobs = [];
        if ($worker->workerProfile && $worker->workerProfile->availability) {
            $suggestedJobs = $this->jobRepository->getInstance()
                ->whereNull('worker_id')
                ->where('status', JobStatusConst::WAITING_FOR_QUOTATION)
                ->whereDoesntHave('invitedWorkers')
                ->with(['serviceCategory', 'area'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        // My jobs
        $myJobs = $this->jobRepository->getInstance()
            ->where('worker_id', $userId)
            ->whereNotIn('status', [JobStatusConst::COMPLETED, JobStatusConst::CANCELLED, JobStatusConst::EXPIRED])
            ->with(['serviceCategory', 'customer', 'area'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return [
            'worker' => [
                'name' => $worker->name,
                'avatar' => $worker->avatar ? $worker->avatar->getUrl() : null,
                'is_online' => $worker->workerProfile ? $worker->workerProfile->availability : false,
            ],
            'summary' => $summary,
            'suggested_jobs' => $suggestedJobs,
            'my_jobs' => $myJobs,
        ];
    }

    public function toggleStatus(int $userId, bool $isOnline): void
    {
        $this->beginTransaction();
        try {
            $profile = $this->workerProfileRepository->getInstance()->where('user_id', $userId)->first();
            if ($profile) {
                $this->workerProfileRepository->update($profile->id, ['availability' => $isOnline]);
            }
            $this->commitTransaction();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
