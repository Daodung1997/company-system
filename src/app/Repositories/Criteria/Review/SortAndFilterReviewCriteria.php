<?php

namespace App\Repositories\Criteria\Review;

use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterReviewCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $searchConditions = [])
    {
        parent::__construct($filters, $sorts, $searchConditions);
    }

    public function apply($model, $repository)
    {
        $select = ['*'];
        $relationship = ['reviewer', 'job', 'target'];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['created_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 't_reviews.id',
            'created_at' => 't_reviews.created_at',
            'rating' => 't_reviews.rating',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            'job_id' => 't_reviews.job_id',
            'reviewer_id' => 't_reviews.reviewer_id',
            'target_id' => 't_reviews.target_id',
            'rating' => 't_reviews.rating',
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'comment' => 't_reviews.comment',
        ]);
    }
}
