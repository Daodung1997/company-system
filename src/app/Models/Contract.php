<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'company_name',
        'company_tax_code',
        'company_address',
        'company_representative',
        'company_representative_role',
        'contract_code',
        'type',
        'employment_type',
        'is_36_agreement_applicable',
        'overtime_allowance_included',
        'included_overtime_hours',
        'probation_period_months',
        'insurance_enrolled',
        'sign_date',
        'start_date',
        'end_date',
        'value',
        'status',
        
        // Advanced Labor Contract Fields
        'job_title',
        'work_location',
        'working_hours_per_day',
        'probation_salary_percentage',
        'bank_name',
        'bank_account_number',

        // Advanced Commercial/Partner/Vendor Contract Fields
        'partner_name',
        'partner_tax_code',
        'partner_representative',
        'partner_representative_role',
        'partner_address',
        'payment_method',
        'payment_terms',
        'billing_cycle',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_36_agreement_applicable' => 'boolean',
        'overtime_allowance_included' => 'boolean',
        'included_overtime_hours' => 'integer',
        'probation_period_months' => 'integer',
        'working_hours_per_day' => 'decimal:2',
        'probation_salary_percentage' => 'integer',
        'sign_date' => 'date:Y-m-d',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'value' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
