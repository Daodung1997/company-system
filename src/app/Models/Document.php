<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends BaseMasterModel
{
    use HasFactory, SoftDeletes;

    public const TABLE_NAME = 't_documents';

    const PREFIX_CODE = 'DOC';

    const MAX_LENGTH_CODE = 20;

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'origin_name',
        'file_path',
        'disk',
        'extension',
        'filesize',
        'documentable_id',
        'documentable_type',
        'employee_id',
        'contract_id',
        'transaction_id',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the owning documentable model.
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the Employee who owns the document.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /**
     * Get the Contract associated with the document.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id');
    }

    /**
     * Get the document download or view URL.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function url(): string
    {
        return $this->getUrlAttribute();
    }
}
