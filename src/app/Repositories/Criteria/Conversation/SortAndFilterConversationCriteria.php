<?php

namespace App\Repositories\Criteria\Conversation;

use App\Models\BaseMasterModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterConversationCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
    {
        $select = [
            't_conversations.*',
        ];
        // Standard relations needed for inbox
        $relationship = ['participants', 'lastMessage', 'creator'];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['last_message_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 't_conversations.id',
            'created_at' => 't_conversations.created_at',
            'last_message_at' => 't_conversations.last_message_at',
        ]);
    }

    public function filter($builder)
    {
        // Example: Filter by type or status
        // Usually inbox filtering by user_id is handled before criteria or via a special filter
        return $this->filterByConditions($builder, $this->filters, [
            'type' => 't_conversations.type',
            'status' => 't_conversations.status',
            'related_id' => 't_conversations.related_id',
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'last_message_content' => 't_conversations.last_message_content',
        ]);
    }
}
