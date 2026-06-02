<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeRelative extends BaseMasterModel
{
    use HasFactory;

    public const PREFIX_CODE = 'REL';

    public const TABLE_NAME = 'employee_relatives';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'employee_id',
        'relationship',
        'full_name',
        'full_name_kana',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'identity_number',
        'occupation',
        'address',
        'is_emergency_contact',
        'is_dependent',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_emergency_contact' => 'boolean',
            'is_dependent' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
