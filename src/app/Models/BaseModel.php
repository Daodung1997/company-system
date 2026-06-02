<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    const PREFIX_CODE = null;

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (isset(auth()->user()->code)) {
                $model->created_by = auth()->user()->code;
            }
        });

        static::updating(function ($model) {
            if (isset(auth()->user()->code)) {
                $model->updated_by = auth()->user()->code;
            }
        });
    }

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'code');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'code');
    }
}
