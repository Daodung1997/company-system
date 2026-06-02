<?php

namespace App\Repositories\Criteria\Admin\Finance;

use App\Models\BaseModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterPaymentCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): BaseModel|Builder|null
    {
        $select = ['t_payments.*'];
        // Eager load job, customer, worker, serviceCategory
        $relationship = ['job', 'job.customer', 'job.worker', 'job.serviceCategory'];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function filter($builder): BaseModel|Builder|null
    {
        // Standard filters
        $builder = $this->filterByConditions($builder, $this->filters, [
            'status' => 't_payments.status',
            'payment_method' => 't_payments.payment_method',
        ]);

        // Filter by created_at range
        if (! empty($this->filters['date_from'])) {
            $builder = $builder->whereDate('t_payments.created_at', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $builder = $builder->whereDate('t_payments.created_at', '<=', $this->filters['date_to']);
        }

        // Filter by Job Status
        if (! empty($this->filters['job_status'])) {
            $builder = $builder->whereHas('job', function ($query) {
                $query->whereIn('status', explode(',', $this->filters['job_status']));
            });
        }

        return $builder;
    }

    public function sort($builder): BaseModel|Builder|null
    {
        if (empty($this->sorts)) {
            $this->sorts = ['created_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 't_payments.id',
            'created_at' => 't_payments.created_at',
            'amount' => 't_payments.amount',
            'status' => 't_payments.status',
        ]);
    }

    public function search($builder): BaseModel|Builder|null
    {
        if (! empty($this->searchConditions['keyword'])) {
            $keyword = $this->searchConditions['keyword'];
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $keyword);

            $builder = $builder->where(function ($query) use ($keyword) {
                $query->where('t_payments.code', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('job', function ($q) use ($keyword) {
                        $q->where('code', 'LIKE', "%{$keyword}%")
                            ->orWhereHas('customer', function ($q2) use ($keyword) {
                                $q2->where('name', 'LIKE', "%{$keyword}%");
                            })
                            ->orWhereHas('worker', function ($q3) use ($keyword) {
                                $q3->where('name', 'LIKE', "%{$keyword}%");
                            });
                    });
            });
        }

        return $builder;
    }

    // Overriding searchByConditions to handle Closure logic if Trait doesn't support it strictly
    // The Trait usually expects string column names. Let's check the Trait again.
    // The Trait supports: 'key' => 'column' or 'key' => ['col1', 'col2'].
    // It DOES NOT support Closures in the searchable array based on the `SortFilterSearchCriteria.php` I read.
    // So I need to implement custom search logic for relations in `search()` and remove those from `searchByConditions` call if strict.
    // Actually the trait matches `searchFields[$key]` to `$value`.

    // Correction: I will handle Relation Search separately.
}
