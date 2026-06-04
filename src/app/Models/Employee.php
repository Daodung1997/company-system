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
        'date_of_birth',
        'gender',
        'hometown',
        'place_of_birth',
        'nationality',
        'ethnicity',
        'religion',
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
            'date_of_birth' => 'date',
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

    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class, 'employee_id', 'id');
    }

    public function relatives()
    {
        return $this->hasMany(EmployeeRelative::class, 'employee_id', 'id');
    }

    public function workHistories()
    {
        return $this->hasMany(EmployeeWorkHistory::class, 'employee_id', 'id')->orderBy('start_date', 'asc');
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

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id', 'id');
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

    /**
     * Check if user has permission
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermissionTo(string $permission): bool
    {
        $rolePermissions = [
            'ADMIN' => ['*'],
            'MANAGER' => ['*'], // Admin / Giám đốc sees all
            
            // HR (Nhân sự) sees employee management, timesheets, and contracts
            'HR' => [
                'view-employees', 'create-employees', 'update-employees', 'delete-employees',
                'view-timesheets', 'approve-timesheets',
                'view-leave-requests', 'approve-leave-requests',
                'view-contracts', 'create-contracts', 'update-contracts',
                'view-documents', 'upload-documents',
                'view-compliance', 'view-dashboard',
            ],
            
            // ACCOUNTANT (Kế toán) sees financial transactions, documents, payslips, contracts, and compliance
            'ACCOUNTANT' => [
                'view-timesheets',
                'view-payslips', 'create-payslips', 'update-payslips',
                'view-transactions', 'create-transactions', 'update-transactions', 'delete-transactions',
                'view-contracts', 'create-contracts', 'update-contracts',
                'view-documents', 'upload-documents',
                'view-compliance', 'view-dashboard',
            ],
            
            // STAFF (Nhân viên) only sees personal files/profile/timesheets
            'STAFF' => [
                'view-own-profile', 'update-own-profile',
                'record-timesheet', 'view-own-timesheet',
                'create-leave-request', 'view-own-leave-requests',
                'view-own-contracts', 'view-own-documents', 'upload-own-documents',
            ],
        ];

        $permissions = $rolePermissions[$this->role] ?? [];
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }
}
