<?php

namespace App\Repositories;

use App\Models\BaseMasterModel;
use App\Models\BaseModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class Repository
 */
class Repository implements RepositoryInterface
{
    /**
     * @var BaseMasterModel
     */
    protected $model;

    /**
     * Collection of Criteria
     *
     * @var Collection
     */
    protected $criteria;

    /**
     * @var bool
     */
    protected $skipCriteria = false;

    /**
     * Repository constructor.
     */
    public function __construct(BaseModel|Model $model)
    {
        $this->model = $model;
        $this->criteria = new Collection;
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*'])
    {
        $this->applyCriteria();
        $model = $this->model->get($columns);
        $this->resetModel();

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $limit, array $columns = ['*'])
    {
        $this->applyCriteria();
        $model = $this->model->paginate($limit, $columns);
        $this->resetModel();

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function first(array $columns = ['*'])
    {
        $this->applyCriteria();
        $model = $this->model->first($columns);
        $this->resetModel();

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data)
    {
        $this->resetModel();

        return $this->model->create($data);
    }

    public function updateOrCreate($attributes, $values)
    {
        $this->resetModel();

        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data)
    {
        return $this->model->where($this->model->getPrimaryKey(), '=', $id)->update($data);
    }

    public function modelUpdate(array $data)
    {
        $status = $this->model->update($data);
        $this->resetModel();

        return $status;
    }

    /**
     * {@inheritDoc}
     */
    public function delete()
    {
        $status = $this->model->delete();
        $this->resetModel();

        return $status;
    }

    public function count()
    {
        $this->applyCriteria();

        return $this->model->count();
    }

    /**
     * {@inheritDoc}
     */
    public function with(array $relations)
    {
        $this->model = $this->model->with($relations);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withCount(array $relations)
    {
        $this->model = $this->model->withCount($relations);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id, array $columns = ['*'])
    {
        $this->applyCriteria();
        $model = $this->model->find($id, $columns);
        $this->resetModel();

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function findWhere(array $where)
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$field, $condition, $val] = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function findWhereIn(string $field, array $values)
    {
        $this->model = $this->model->whereIn($field, $values);

        return $this;
    }

    /**
     * Delete where conditions.
     *
     * @return mixed
     */
    public function deleteWhere(array $where)
    {
        $this->applyCriteria();

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$field, $condition, $val] = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }

        $deleted = $this->model->delete();
        $this->resetModel();

        return $deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function findWhereNotIn(string $field, array $values)
    {
        $this->model = $this->model->whereNotIn($field, $values);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $relationship, string $operator, int $count)
    {
        $this->model = $this->model->has($relationship, $operator, $count);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function orderBy(string $column, string $direction = 'asc')
    {
        $this->model = $this->model->orderBy($column, $direction);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function applyCriteria()
    {
        if ($this->skipCriteria === true) {
            return $this;
        }

        $criteria = $this->getCriteria();

        if ($criteria) {
            foreach ($criteria as $c) {
                if ($c instanceof CriteriaInterface) {
                    $this->model = $c->apply($this->model, $this);
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function pushCriteria(CriteriaInterface $criteria)
    {
        $this->criteria->push($criteria);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getByCriteria(CriteriaInterface $criteria)
    {
        $this->model = $criteria->apply($this->model, $this);
        $results = $this->model->get();
        $this->resetModel();

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function resetCriteria()
    {
        $this->criteria = new Collection;
        $this->resetModel();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function firstOrCreate(array $attributes, array $data)
    {
        $this->resetModel();

        return $this->model->firstOrCreate($attributes, $data);
    }

    /**
     * Get Collection of Criteria
     *
     * @return Collection
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Skip Criteria
     *
     * @param  bool  $status
     * @return $this
     */
    public function skipCriteria($status = true)
    {
        $this->skipCriteria = $status;

        return $this;
    }

    protected function resetModel(): void
    {
        $this->model = $this->model->getModel();
    }

    public function sharedLock()
    {
        $this->model = $this->model->sharedLock();

        return $this;
    }

    public function lockForUpdate()
    {
        $this->model = $this->model->lockForUpdate();

        return $this;
    }

    public function withTrash()
    {
        $this->model = $this->model->withTrashed();

        return $this;
    }

    public function chunk(int $chunkSize, $callback)
    {
        $this->applyCriteria();
        $result = $this->model->chunk($chunkSize, $callback);
        $this->resetModel();

        return $result;
    }

    public function available()
    {
        $this->model = $this->model->available();

        return $this;
    }

    public function getInstance()
    {
        return $this->model;
    }

    public function select($columns = [])
    {
        $this->applyCriteria();
        $result = $this->model->select($columns);

        return $result;
    }

    public function when($condition, array $where)
    {
        $this->model = $this->model->when($condition, function ($q) use ($where) {
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    [$field, $condition, $val] = $value;
                    $this->model = $q->where($field, $condition, $val);
                } else {
                    $this->model = $q->where($field, '=', $value);
                }
            }
        });

        return $this;
    }

    /**
     * Find a model by its code.
     *
     * @param  string  $code
     * @param  array  $columns
     * @return mixed
     */
    public function findByCode(array $criteria)
    {
        $this->applyCriteria();
        [$field, $value] = $criteria;

        $this->resetModel();

        $model = $this->model->where($field, '=', $value)->first();

        $this->resetModel();

        return $model;
    }

    public function getByColumn(array $criteria)
    {
        $model = $this->model->where($criteria);

        $this->resetModel();

        return $model->get();
    }
}
