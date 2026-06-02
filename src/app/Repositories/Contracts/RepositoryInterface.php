<?php

namespace App\Repositories\Contracts;

use App\Models\AbstractModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface RepositoryInterface
 */
interface RepositoryInterface
{
    /**
     * @return Collection
     */
    public function all(array $columns = ['*']);

    /**
     * @return LengthAwarePaginator
     */
    public function paginate(int $limit, array $columns = ['*']);

    /**
     * @return AbstractModel
     */
    public function first(array $columns = ['*']);

    /**
     * @return AbstractModel
     */
    public function create(array $data);

    /**
     * @return AbstractModel
     */
    public function updateOrCreate($attributes, $values);

    /**
     * @return bool
     */
    public function update(int $id, array $data);

    /**
     * @return int
     */
    public function delete();

    /**
     * @return int
     */
    public function count();

    /**
     * @return $this
     */
    public function with(array $relations);

    /**
     * @return $this
     */
    public function withCount(array $relations);

    /**
     * @return AbstractModel|null
     */
    public function find(int $id, array $columns = ['*']);

    /**
     * @return $this
     */
    public function findWhere(array $where);

    /**
     * @return $this
     */
    public function findWhereIn(string $field, array $values);

    /**
     * @return $this
     */
    public function findWhereNotIn(string $field, array $values);

    /**
     * @return $this
     */
    public function has(string $relationship, string $operator, int $count);

    /**
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc');

    /**
     * Apply criteria in current Query
     *
     * @return $this
     */
    public function applyCriteria();

    /**
     * @return $this
     */
    public function pushCriteria(CriteriaInterface $criteria);

    /**
     * @return Collection
     */
    public function getByCriteria(CriteriaInterface $criteria);

    /**
     * Reset all Criteria
     *
     * @return $this
     */
    public function resetCriteria();

    /**
     * @return AbstractModel
     */
    public function firstOrCreate(array $attributes, array $data);
}
