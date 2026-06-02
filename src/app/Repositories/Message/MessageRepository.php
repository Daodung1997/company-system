<?php

namespace App\Repositories\Message;

use App\Models\Message;
use App\Repositories\Repository;

class MessageRepository extends Repository
{
    public function model(): string
    {
        return Message::class;
    }

    public function __construct(Message $model)
    {
        parent::__construct($model);
    }
}
