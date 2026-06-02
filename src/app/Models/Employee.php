<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Employee extends BaseAuthenticateModel implements JWTSubject
{
    use HasFactory, Notifiable;

    public const PREFIX_CODE = 'EMP';

    public const TABLE_NAME = 'employees';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'department_id',
        'job_title_id',
        'full_name',
        'full_name_kana',
        'romaji_name',
        'email',
        'phone',
        'password',
        'identity_type',
        'identity_number',
        'zairyu_card_expiry',
        'tax_code',
        'social_insurance_code',
        'pension_number',
        'employment_insurance_number',
        'bank_code',
        'bank_branch_code',
        'bank_account_type',
        'bank_account_number',
        'bank_account_holder_kana',
        'role',
        'dependents_count',
        'address_registered',
        'address_current',
        'status',
        'join_date',
        'must_change_password',
        'avatar',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'zairyu_card_expiry' => 'date',
            'join_date' => 'date',
            'must_change_password' => 'boolean',
        ];
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class, 'employee_id', 'id');
    }

    public function relatives()
    {
        return $this->hasMany(EmployeeRelative::class, 'employee_id', 'id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'employee_id', 'id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'employee_id', 'id')->whereNull('contract_id')->whereNull('transaction_id');
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class, 'employee_id', 'id')->where('status', 'ACTIVE');
    }

    public function emergencyContacts()
    {
        return $this->relatives()->where('is_emergency_contact', true);
    }

    public function dependents()
    {
        return $this->relatives()->where('is_dependent', true);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
