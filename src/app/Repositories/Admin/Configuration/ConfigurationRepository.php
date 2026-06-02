<?php

namespace App\Repositories\Admin\Configuration;

use App\Models\Configuration;
use App\Repositories\Repository;

class ConfigurationRepository extends Repository
{
    public function __construct(Configuration $model)
    {
        $this->model = $model;
    }

    public function getValue(string $key)
    {
        return $this->model->where('key', $key)->value('value');
    }

    public function updateValue(string $key, $value)
    {
        return $this->model->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
