<?php

namespace App\Repositories\Criteria\Job;

use App\Models\BaseMasterModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterJobCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
    {
        $select = ['t_jobs.*'];
        $relationship = ['media', 'serviceCategory', 'area', 'customer', 'worker', 'payment']; // Eager load common relations

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function filter($builder): BaseMasterModel|Builder|null
    {
        // Standard filters setup
        $standardFilters = [
            'status' => 't_jobs.status',
            'service_id' => 't_jobs.service_id',
            'customer_id' => 't_jobs.customer_id',
            'scheduled_date' => 't_jobs.scheduled_date',
        ];

        // Only apply strict worker_id filter if it's NOT a worker relationship query
        if (! isset($this->filters['worker_job_type'])) {
            $standardFilters['worker_id'] = 't_jobs.worker_id';
        }

        $builder = $this->filterByConditions($builder, $this->filters, $standardFilters);

        if (! empty($this->filters['worker_list_tab']) && ! empty($this->filters['worker_id'])) {
            $tab = $this->filters['worker_list_tab'];
            $workerId = $this->filters['worker_id'];

            if ($tab === 'in_progress') {
                $statusIn = \App\Constants\Master\Models\Job\JobStatusConst::inProgressStatuses();
                $builder = $builder->where(function ($q) use ($workerId, $statusIn) {
                    $q->where(function ($q2) use ($workerId, $statusIn) {
                        $q2->where('t_jobs.worker_id', $workerId)
                            ->whereIn('t_jobs.status', $statusIn);
                    })->orWhereHas('quotations', function ($q3) use ($workerId) {
                        $q3->where('worker_id', $workerId)
                            ->where('status', \App\Constants\Master\Models\Quotation\QuotationStatusConst::PENDING);
                    });
                });
            } elseif ($tab === 'completed') {
                $statusIn = \App\Constants\Master\Models\Job\JobStatusConst::completedStatuses();
                $builder = $builder->where(function ($q) use ($workerId, $statusIn) {
                    $q->where(function ($q2) use ($workerId, $statusIn) {
                        $q2->where('t_jobs.worker_id', $workerId)
                            ->whereIn('t_jobs.status', $statusIn);
                    })->orWhereHas('quotations', function ($q3) use ($workerId) {
                        $q3->where('worker_id', $workerId)
                            ->where('status', \App\Constants\Master\Models\Quotation\QuotationStatusConst::REJECTED);
                    });
                });
            }
        } elseif (! empty($this->filters['status_in'])) {
            $builder = $builder->whereIn('t_jobs.status', $this->filters['status_in']);
        }

        // Filter by service IDs (for worker available jobs)
        if (! empty($this->filters['service_ids'])) {
            $builder = $builder->whereIn('t_jobs.service_id', $this->filters['service_ids']);
        }

        // Filter by area IDs (for worker available jobs)
        if (! empty($this->filters['area_ids'])) {
            $builder = $builder->whereIn('t_jobs.area_id', $this->filters['area_ids']);
        }

        // Exclude job IDs (for jobs worker already quoted)
        if (! empty($this->filters['exclude_ids'])) {
            $builder = $builder->whereNotIn('t_jobs.id', $this->filters['exclude_ids']);
        }

        // Filter for worker's jobs (quoted or assigned)
        if (! empty($this->filters['worker_id'])) {
            $workerId = $this->filters['worker_id'];
            $workerJobType = $this->filters['worker_job_type'] ?? null;

            switch ($workerJobType) {
                case 'quoted':
                    // Only jobs worker has quoted but is NOT assigned to
                    $builder = $builder->where(function ($query) use ($workerId) {
                        $query->whereHas('quotations', function ($q) use ($workerId) {
                            $q->where('worker_id', $workerId);
                        })->where(function ($q) use ($workerId) {
                            $q->whereNull('t_jobs.worker_id')
                                ->orWhere('t_jobs.worker_id', '!=', $workerId);
                        });
                    });
                    break;

                case 'assigned':
                    // Only jobs where worker is assigned (accepted quotation)
                    $builder = $builder->where('t_jobs.worker_id', $workerId);
                    break;

                default:
                    // All: assigned OR quoted
                    $builder = $builder->where(function ($query) use ($workerId) {
                        $query->where('t_jobs.worker_id', $workerId)
                            ->orWhereHas('quotations', function ($q) use ($workerId) {
                                $q->where('worker_id', $workerId);
                            });
                    });
                    break;
            }
        }

        return $builder;
    }

    public function sort($builder): BaseMasterModel|Builder|null
    {
        if (empty($this->sorts)) {
            $this->sorts = ['created_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 't_jobs.id',
            'created_at' => 't_jobs.created_at',
            'scheduled_date' => 't_jobs.scheduled_date',
            'quotation_price' => 't_jobs.quotation_price',
        ]);
    }

    public function search($builder): BaseMasterModel|Builder|null
    {
        if (! empty($this->searchConditions['customer_name'])) {
            $builder = $builder->whereHas('customer', function ($q) {
                $q->where('name', 'LIKE', '%'.$this->searchConditions['customer_name'].'%');
            });
        }
        if (! empty($this->searchConditions['worker_name'])) {
            $builder = $builder->whereHas('worker', function ($q) {
                $q->where('name', 'LIKE', '%'.$this->searchConditions['worker_name'].'%');
            });
        }

        return $this->searchByConditions($builder, $this->searchConditions, [
            'description' => 't_jobs.description',
            'address' => 't_jobs.address',
            'code' => 't_jobs.code',
        ]);
    }
}
