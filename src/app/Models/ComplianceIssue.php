<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceIssue extends BaseModel
{
    use HasFactory, SoftDeletes;

    public const TABLE_NAME = 't_compliance_issues';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'employee_id',
        'contract_id',
        'transaction_id',
        'issue_type',
        'severity',
        'description',
        'status',
        'resolved_at',
        'resolved_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];



    /**
     * Get the Employee associated with this issue.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /**
     * Get the Contract associated with this issue.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id');
    }

    /**
     * Get the Transaction associated with this issue.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
