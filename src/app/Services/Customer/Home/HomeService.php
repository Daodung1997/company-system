<?php

namespace App\Services\Customer\Home;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Repositories\Job\JobRepository;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\ServiceCategory\ServiceCategoryRepository;
use App\Services\AbstractService;

class HomeService extends AbstractService
{
    public function __construct(
        protected ServiceCategoryRepository $serviceCategoryRepository,
        protected JobRepository $jobRepository,
        protected NotificationRepository $notificationRepository
    ) {}

    public function getHome(int $userId): array
    {
        $user = auth()->user();

        // This is a simplified approach, ideally moved to Repository
        $unreadCount = $this->notificationRepository->getInstance()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        // Ideally moved to ServiceCategoryRepository
        $categories = $this->serviceCategoryRepository->getInstance()
            ->where('status', \App\Constants\Master\Models\ServiceCategory\ServiceCategoryStatusConst::ACTIVE)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        // Ideally moved to JobRepository
        $ongoingRequests = $this->jobRepository->getInstance()
            ->where('customer_id', $userId)
            ->whereNotIn('status', [JobStatusConst::COMPLETED, JobStatusConst::CANCELLED, JobStatusConst::REFUNDED, JobStatusConst::EXPIRED])
            ->with(['serviceCategory', 'worker'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return [
            'user' => [
                'name' => $user->name,
                'avatar' => $user->avatar ? $user->avatar->getUrl() : null,
            ],
            'notifications' => [
                'unread_count' => $unreadCount,
            ],
            'categories' => $categories,
            'ongoing_requests' => $ongoingRequests,
            'suggested_workers' => [],
            'banners' => [],
        ];
    }
}
