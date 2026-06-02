<?php

namespace App\Repositories\Criteria\Job;

use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JobVisibilityCriteria implements CriteriaInterface
{
    protected $worker;

    protected ?string $type;

    /**
     * @param  mixed  $worker
     * @param  string|null  $type  Filter type: 'open', 'invited', or null/all
     */
    public function __construct($worker, ?string $type = null)
    {
        $this->worker = $worker;
        $this->type = $type;
    }

    /**
     * Apply criteria in query repository
     *
     * @param  Builder|Model  $model
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where(function ($query) {
            switch ($this->type) {
                case 'invited':
                    // Only jobs where this worker is explicitly invited
                    $query->whereHas('invitedWorkers', function ($subQ) {
                        $subQ->where('worker_id', $this->worker->id);
                    });
                    break;

                case 'open':
                    // Only jobs with no invited workers (public/open jobs)
                    $query->whereDoesntHave('invitedWorkers');
                    break;

                default:
                    // All visible jobs: invited OR open
                    $query->whereHas('invitedWorkers', function ($subQ) {
                        $subQ->where('worker_id', $this->worker->id);
                    })->orWhereDoesntHave('invitedWorkers');
                    break;
            }
        });
    }
}
