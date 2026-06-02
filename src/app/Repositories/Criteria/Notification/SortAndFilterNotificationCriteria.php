<?php

namespace App\Repositories\Criteria\Notification;

use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterNotificationCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        $this->filters = $filters;
        $this->sorts = $sorts;
        $this->searchConditions = $search;
    }

    public function apply($model, \App\Repositories\Contracts\RepositoryInterface $repository)
    {
        $select = ['*'];
        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['id' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 't_notifications.id',
            'created_at' => 't_notifications.created_at',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            'user_id' => 't_notifications.user_id',
            'type' => 't_notifications.type',
            'is_read' => function ($q, $val) {
                if ($val) {
                    $q->whereNotNull('read_at');
                } else {
                    $q->whereNull('read_at');
                }
            },
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'title' => 't_notifications.title',
            'body' => 't_notifications.body',
        ]);
    }
}
