<?php

namespace Tests\Feature\Contract;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    protected $department;
    protected $employee;
    protected $manager;
    protected $token;
    protected $managerToken;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Department
        $this->department = Department::create([
            'name' => 'Phòng Nhân sự (HR)',
            'description' => 'HR Department',
        ]);

        // 2. Seed Employee (Staff)
        $this->employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn Nhân Viên',
            'email' => 'employee@compliance.vn',
            'phone' => '0987654323',
            'password' => Hash::make('password123'),
            'role' => 'STAFF',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        // 3. Seed Manager (Manager)
        $this->manager = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Trần Thị Quản Lý',
            'email' => 'manager@compliance.vn',
            'phone' => '0987654324',
            'password' => Hash::make('password123'),
            'role' => 'MANAGER',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        // 4. Auth Tokens
        $loginResponse1 = $this->postJson('/api/auth/login', [
            'username' => 'employee@compliance.vn',
            'password' => 'password123',
        ]);
        $this->token = $loginResponse1->json('data.access_token');

        $loginResponse2 = $this->postJson('/api/auth/login', [
            'username' => 'manager@compliance.vn',
            'password' => 'password123',
        ]);
        $this->managerToken = $loginResponse2->json('data.access_token');
    }

    public function test_user_can_create_contract()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/contracts', [
                'employee_id' => $this->employee->id,
                'type' => 'LABOR',
                'employment_type' => 'SEISHAIN',
                'is_36_agreement_applicable' => true,
                'overtime_allowance_included' => true,
                'included_overtime_hours' => 20,
                'probation_period_months' => 2,
                'insurance_enrolled' => 'SOCIAL_VN,HEALTH_VN',
                'sign_date' => '2026-06-01',
                'start_date' => '2026-06-01',
                'end_date' => '2027-06-01',
                'value' => 15000000.00,
                'status' => 'ACTIVE',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'id',
                    'contract_code',
                    'employee_id',
                    'type',
                    'value',
                    'status',
                ]
            ]);

        $this->assertDatabaseHas('contracts', [
            'employee_id' => $this->employee->id,
            'type' => 'LABOR',
            'employment_type' => 'SEISHAIN',
            'is_36_agreement_applicable' => 1,
            'overtime_allowance_included' => 1,
            'included_overtime_hours' => 20,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_cannot_create_overlapping_labor_contract()
    {
        // 1. Create first active labor contract from 2026-06-01 to 2027-06-01
        Contract::create([
            'employee_id' => $this->employee->id,
            'contract_code' => 'HDLD-001',
            'type' => 'LABOR',
            'employment_type' => 'SEISHAIN',
            'sign_date' => '2026-06-01',
            'start_date' => '2026-06-01',
            'end_date' => '2027-06-01',
            'status' => 'ACTIVE',
        ]);

        // 2. Try to create second active labor contract in overlapping period (e.g. starting 2026-10-01)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/contracts', [
                'employee_id' => $this->employee->id,
                'type' => 'LABOR',
                'employment_type' => 'SEISHAIN',
                'sign_date' => '2026-10-01',
                'start_date' => '2026-10-01',
                'end_date' => '2027-10-01',
                'status' => 'ACTIVE',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', 'CNT_003');
    }

    public function test_user_can_list_contracts_under_company()
    {
        // Seed a contract
        Contract::create([
            'employee_id' => $this->employee->id,
            'contract_code' => 'HDLD-LIST-TEST',
            'type' => 'LABOR',
            'sign_date' => '2026-06-01',
            'start_date' => '2026-06-01',
            'status' => 'ACTIVE',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/contracts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.contract_code', 'HDLD-LIST-TEST');
    }

    public function test_user_can_update_contract()
    {
        $contract = Contract::create([
            'employee_id' => $this->employee->id,
            'contract_code' => 'HDLD-UPDATE-TEST',
            'type' => 'LABOR',
            'sign_date' => '2026-06-01',
            'start_date' => '2026-06-01',
            'status' => 'ACTIVE',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->putJson('/api/contracts/' . $contract->id, [
                'employee_id' => $this->employee->id,
                'contract_code' => 'HDLD-UPDATED-CODE',
                'type' => 'LABOR',
                'sign_date' => '2026-06-01',
                'start_date' => '2026-06-01',
                'value' => 20000000.00,
                'status' => 'ACTIVE',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.contract_code', 'HDLD-UPDATED-CODE');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'contract_code' => 'HDLD-UPDATED-CODE',
            'value' => 20000000.00,
        ]);
    }

    public function test_user_can_delete_contract()
    {
        $contract = Contract::create([
            'employee_id' => $this->employee->id,
            'contract_code' => 'HDLD-DELETE-TEST',
            'type' => 'LABOR',
            'sign_date' => '2026-06-01',
            'start_date' => '2026-06-01',
            'status' => 'ACTIVE',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->deleteJson('/api/contracts/' . $contract->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('contracts', [
            'id' => $contract->id,
        ]);
    }
}
