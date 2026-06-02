<?php

namespace App\Observers;

use App\Models\Review;
use App\Models\User;
use App\Models\WorkerProfile;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        $this->updateWorkerRating($review->target_id);
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Review $review): void
    {
        $this->updateWorkerRating($review->target_id);
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        $this->updateWorkerRating($review->target_id);
    }

    private function updateWorkerRating($workerId)
    {
        // workerId in Review is 'target_id', which refers to User ID.
        // WorkerProfile is also linked to User ID.

        $workerProfile = WorkerProfile::where('user_id', $workerId)->first();
        if (! $workerProfile) {
            return;
        }

        // Calculate Average Rating for this User (as worker)
        // Review target_id = user_id
        $avgRating = Review::where('target_id', $workerId)->avg('rating') ?? 0;

        $workerProfile->update([
            'avg_rating' => round($avgRating, 2),
        ]);
    }
}
