<?php

namespace Tests\Feature\Timesheet;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimesheetTest extends TestCase
{
    use RefreshDatabase;

    protected $department;
    protected $employee;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'Phòng IT',
            'description' => 'IT Department',
        ]);

        $this->employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn Chấm Công',
            'email' => 'employee@compliance.vn',
            'phone' => '0987654322',
            'password' => Hash::make('password123'),
            'role' => 'STAFF',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'employee@compliance.vn',
            'password' => 'password123',
        ]);

        $this->token = $loginResponse->json('data.access_token');
    }

    public function test_employee_can_check_in_today_present()
    {
        // Mock time to 08:30:00 local time
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 8, 30, 0, 'Asia/Ho_Chi_Minh'));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/timesheets/check-in', [
                'timezone' => 'Asia/Ho_Chi_Minh',
                'note' => 'Đến sớm làm việc',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'PRESENT')
            ->assertJsonPath('data.note', 'Đến sớm làm việc');

        $this->assertDatabaseHas('timesheets', [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-01',
            'status' => 'PRESENT',
        ]);

        Carbon::setTestNow(); // Reset mocked time
    }

    public function test_employee_can_check_in_today_late()
    {
        // Mock time to 09:15:00 local time
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 9, 15, 0, 'Asia/Ho_Chi_Minh'));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/timesheets/check-in', [
                'timezone' => 'Asia/Ho_Chi_Minh',
                'note' => 'Kẹt xe',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'LATE')
            ->assertJsonPath('data.note', 'Kẹt xe');

        $this->assertDatabaseHas('timesheets', [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-01',
            'status' => 'LATE',
        ]);

        Carbon::setTestNow();
    }

    public function test_employee_cannot_check_in_twice_today()
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 8, 30, 0, 'Asia/Ho_Chi_Minh'));

        // First check-in
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/timesheets/check-in');

        // Second check-in should fail
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/timesheets/check-in');

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', 'TMS_002');

        Carbon::setTestNow();
    }

    public function test_employee_can_check_out_today()
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 8, 30, 0, 'Asia/Ho_Chi_Minh'));

        // Check-in
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/timesheets/check-in');

        // Mock checkout time
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 18, 0, 0, 'Asia/Ho_Chi_Minh'));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/timesheets/check-out', [
                'timezone' => 'Asia/Ho_Chi_Minh',
                'note' => 'Hoàn thành công việc',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.check_out', '2026-06-01 18:00:00');

        Carbon::setTestNow();
    }

    public function test_employee_can_get_monthly_timesheets()
    {
        // Seed timesheets for the employee
        Timesheet::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-05-01',
            'check_in' => '2026-05-01 08:30:00',
            'check_out' => '2026-05-01 17:30:00',
            'status' => 'PRESENT',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        Timesheet::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-05-02',
            'check_in' => '2026-05-02 09:15:00',
            'check_out' => '2026-05-02 18:00:00',
            'status' => 'LATE',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/timesheets/monthly?year_month=2026-05');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'employee_id',
                        'date',
                        'check_in',
                        'check_out',
                        'status',
                        'timezone',
                        'note',
                    ]
                ]
            ]);
    }

    public function test_manager_can_get_admin_timesheets_list()
    {
        $manager = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn Quản Lý',
            'email' => 'manager@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'MANAGER',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'manager@compliance.vn',
            'password' => 'password123',
        ]);
        $managerToken = $loginResponse->json('data.access_token');

        Timesheet::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-06-01',
            'check_in' => '2026-06-01 08:30:00',
            'check_out' => '2026-06-01 17:30:00',
            'status' => 'PRESENT',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $managerToken)
            ->getJson('/api/timesheets/manage?q=Nguyễn');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'employee_id',
                            'date',
                            'status',
                            'employee',
                        ]
                    ],
                    'meta'
                ]
            ]);
    }

    public function test_manager_can_get_monthly_statistics()
    {
        $manager = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn Quản Lý',
            'email' => 'manager@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'MANAGER',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'manager@compliance.vn',
            'password' => 'password123',
        ]);
        $managerToken = $loginResponse->json('data.access_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $managerToken)
            ->getJson('/api/timesheets/statistics?year_month=2026-06');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    '*' => [
                        'employee_id',
                        'employee_code',
                        'full_name',
                        'email',
                        'total_present',
                        'total_late',
                        'total_absent',
                        'total_working_days',
                    ]
                ]
            ]);
    }

    public function test_manager_can_store_manual_timesheet()
    {
        $manager = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn Quản Lý',
            'email' => 'manager@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'MANAGER',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'manager@compliance.vn',
            'password' => 'password123',
        ]);
        $managerToken = $loginResponse->json('data.access_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $managerToken)
            ->postJson('/api/timesheets/store-manual', [
                'employee_id' => $this->employee->id,
                'date' => '2026-06-01',
                'check_in' => '2026-06-01 08:30:00',
                'check_out' => '2026-06-01 17:30:00',
                'status' => 'PRESENT',
                'note' => 'Bù giờ thủ công',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('timesheets', [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-01',
            'status' => 'PRESENT',
            'note' => 'Bù giờ thủ công',
        ]);
    }

    public function test_monthly_statistics_accounts_for_approved_half_day_leave()
    {
        // 1. Create and approve a morning leave request for the employee on 2026-06-02
        $leave = \App\Models\LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => 'ANNUAL',
            'leave_session' => 'MORNING',
            'start_date' => '2026-06-02',
            'end_date' => '2026-06-02',
            'reason' => 'Đi khám bệnh buổi sáng',
            'status' => 'APPROVED',
            'approved_by' => $this->employee->id, // self approved for testing simplicity
            'approved_at' => now(),
        ]);

        // 2. Create timesheet on 2026-06-02 checking in at 13:10:00 (which is before 13:15:00 morning-off expected start)
        \App\Models\Timesheet::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-06-02',
            'check_in' => '2026-06-02 13:10:00',
            'check_out' => '2026-06-02 17:30:00',
            'status' => 'PRESENT',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        // 3. Login as manager to request statistics
        $manager = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn Quản Lý',
            'email' => 'manager@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'MANAGER',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'manager@compliance.vn',
            'password' => 'password123',
        ]);
        $managerToken = $loginResponse->json('data.access_token');

        // 4. Retrieve statistics
        $response = $this->withHeader('Authorization', 'Bearer ' . $managerToken)
            ->getJson('/api/timesheets/statistics?year_month=2026-06');

        $response->assertStatus(200);

        // Find the stats of the employee
        $employeeStats = collect($response->json('data'))->firstWhere('employee_id', $this->employee->id);
        $this->assertNotNull($employeeStats);

        // Check-in was at 13:10:00 and pushed expected_start was 13:15:00, so it shouldn't count as late
        $this->assertEquals(0, $employeeStats['total_late']);
        $this->assertEquals(0.0, $employeeStats['total_late_hours']);

        // Check timesheets detail in stats response
        $dayStats = collect($employeeStats['timesheets'])->firstWhere('date', '2026-06-02');
        $this->assertNotNull($dayStats);
        $this->assertEquals('13:15:00', $dayStats['expected_start']);
        $this->assertEquals('MORNING', $dayStats['leave_session']);
    }
}
