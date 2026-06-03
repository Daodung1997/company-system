<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'payslips';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'employee_id',
        'year_month',
        'base_salary',
        'standard_working_days',
        'actual_working_days',
        'overtime_hours',
        'overtime_salary',
        'overtime_hours_normal',
        'overtime_salary_normal',
        'overtime_hours_weekend',
        'overtime_salary_weekend',
        'overtime_hours_holiday',
        'overtime_salary_holiday',
        'allowance_attendance',
        'deduction_late',
        'deduction_leave',
        'deduction_union',
        'deduction_tax',
        'advance_payment',
        'net_salary',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'standard_working_days' => 'decimal:2',
        'actual_working_days' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_salary' => 'decimal:2',
        'overtime_hours_normal' => 'decimal:2',
        'overtime_salary_normal' => 'decimal:2',
        'overtime_hours_weekend' => 'decimal:2',
        'overtime_salary_weekend' => 'decimal:2',
        'overtime_hours_holiday' => 'decimal:2',
        'overtime_salary_holiday' => 'decimal:2',
        'allowance_attendance' => 'decimal:2',
        'deduction_late' => 'decimal:2',
        'deduction_leave' => 'decimal:2',
        'deduction_union' => 'decimal:2',
        'deduction_tax' => 'decimal:2',
        'advance_payment' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
