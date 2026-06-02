<?php

namespace App\Repositories\Review;

use App\Models\Review;
use App\Repositories\Repository;

class ReviewRepository extends Repository
{
    public function __construct(Review $model)
    {
        parent::__construct($model);
    }

    /**
     * Get review summary for a worker
     */
    public function getSummary($workerId)
    {
        $query = $this->model->where('target_id', $workerId);

        $avg = $query->avg('rating') ?? 0;
        $total = $query->count();

        // Breakdown
        $breakdown = $this->model->where('target_id', $workerId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Ensure all stars 1-5 exist
        $stars = [];
        for ($i = 5; $i >= 1; $i--) {
            $stars[$i] = $breakdown[$i] ?? 0;
        }

        return [
            'avg_rating' => round($avg, 2),
            'total_reviews' => $total,
            'breakdown' => $stars,
        ];
    }
}
