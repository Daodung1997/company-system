<?php

namespace App\Repositories\Criteria\Message;

use App\Models\BaseMasterModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterMessageCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
    {
        $select = [
            't_messages.*',
        ];
        $relationship = ['sender'];

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
            'id' => 't_messages.id',
            'created_at' => 't_messages.created_at',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            'conversation_id' => 't_messages.conversation_id',
            'sender_id' => 't_messages.sender_id',
            'type' => 't_messages.type',
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'content' => 't_messages.content',
        ]);
    }
}
