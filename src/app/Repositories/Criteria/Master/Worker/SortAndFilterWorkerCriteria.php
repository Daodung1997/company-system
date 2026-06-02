<?php

namespace App\Repositories\Criteria\Master\Worker;

use App\Constants\Master\Models\User\UserRoleConst;
use App\Models\BaseMasterModel;
use App\Models\User;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterWorkerCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
    {
        $select = [User::TABLE_NAME.'.*'];
        $relationship = ['workerProfile'];

        // Base filter: only workers
        $model = $model->where(User::TABLE_NAME.'.role', UserRoleConst::WORKER);

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder): BaseMasterModel|Builder|null
    {
        if (empty($this->sorts)) {
            $this->sorts = ['id' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => User::TABLE_NAME.'.id',
            'name' => User::TABLE_NAME.'.name',
            'email' => User::TABLE_NAME.'.email',
            'created_at' => User::TABLE_NAME.'.created_at',
        ]);
    }

    public function filter($builder): BaseMasterModel|Builder|null
    {
        // Filter by profile_status via whereHas
        if (! empty($this->filters['profile_status'])) {
            $status = $this->filters['profile_status'];
            $builder = $builder->whereHas('workerProfile', function ($q) use ($status) {
                $q->where('profile_status', $status);
            });
        }

        // Filter by activity_status via whereHas
        if (! empty($this->filters['activity_status'])) {
            $activityStatus = $this->filters['activity_status'];
            $builder = $builder->whereHas('workerProfile', function ($q) use ($activityStatus) {
                $q->where('activity_status', $activityStatus);
            });
        }

        return $builder;
    }

    public function search($builder): BaseMasterModel|Builder|null
    {
        if (empty($this->searchConditions)) {
            return $builder;
        }

        return $builder->where(function ($q) {
            $hasSearch = false;

            if (! empty($this->searchConditions['name'])) {
                $q->where(User::TABLE_NAME.'.name', 'like', "%{$this->searchConditions['name']}%");
                $hasSearch = true;
            }

            if (! empty($this->searchConditions['email'])) {
                $method = $hasSearch ? 'orWhere' : 'where';
                $q->$method(User::TABLE_NAME.'.email', 'like', "%{$this->searchConditions['email']}%");
                $hasSearch = true;
            }

            if (! empty($this->searchConditions['phone'])) {
                $method = $hasSearch ? 'orWhereHas' : 'whereHas';
                $q->$method('workerProfile', function ($wq) {
                    $wq->where('phone', 'like', "%{$this->searchConditions['phone']}%");
                });
            }
        });
    }
}
