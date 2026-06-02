<?php

namespace App\Services\User;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Criteria\Review\SortAndFilterReviewCriteria;
use App\Repositories\Job\JobRepository;
use App\Repositories\Review\ReviewRepository;
use App\Services\AbstractService;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewService extends AbstractService
{
    protected $jobRepository;

    public function __construct(
        ReviewRepository $repository,
        JobRepository $jobRepository
    ) {
        $this->repository = $repository;
        $this->jobRepository = $jobRepository;
    }

    public function createReview(array $data, $user)
    {
        $jobId = $data['job_id'];

        // 1. Check if review already exists for this job
        // Although DB might not strictly enforce unique (job_id, reviewer_id) if not added in migration, business rule says 1 Job = 1 Review.
        // Also check if user is the customer of the job is done in Request validation (Rule::exists query).
        // Here we just check duplicate review.

        $exists = $this->repository->getInstance()->where('job_id', $jobId)->exists();
        if ($exists) {
            throw new BusinessException(ExceptionCode::REVIEW_ALREADY_EXISTS, 'Bạn đã đánh giá công việc này rồi.', 422);
        }

        // 2. Identify target (Worker)
        // We need to fetch the job to get worker_id.
        // We can optimize by assuming FE sends valid job_id, but we need worker_id.
        $job = $this->jobRepository->find($jobId);
        if (! $job || ! $job->worker_id) {
            throw new BusinessException(ExceptionCode::ITEM_NOT_FOUND, 'Không tìm thấy thợ thực hiện công việc này.', 404);
        }

        $this->beginTransaction();
        try {
            $data['reviewer_id'] = $user->id;
            $data['target_id'] = $job->worker_id;

            $review = $this->repository->create($data);

            $this->commitTransaction();

            return $review;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function getWorkerReviews($workerId, $data): LengthAwarePaginator
    {
        $limit = $data['limit'] ?? 10;
        $filters = $data['filters'] ?? [];
        $sorts = $data['sorts'] ?? [];

        // Enforce worker_id filter
        $filters['target_id'] = $workerId;

        return $this->repository->pushCriteria(
            new SortAndFilterReviewCriteria($filters, $sorts)
        )->paginate($limit);
    }

    public function getReviewSummary($workerId)
    {
        return $this->repository->getSummary($workerId);
    }
}
