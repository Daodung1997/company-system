<?php

namespace Tests\Feature\Timesheet;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $department;
    protected $staff;
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'Phòng Nhân sự',
            'description' => 'HR Department',
        ]);

        // Create staff
        $this->staff = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn Nhân Viên',
            'email' => 'staff@compliance.vn',
            'phone' => '0987654323',
            'password' => Hash::make('password123'),
            'role' => 'STAFF',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        // Create manager
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
    }

    public function test_employee_can_create_leave_request()
    {
        $response = $this->actingAs($this->staff, 'api')
            ->postJson('/api/leave-requests', [
                'leave_type' => 'ANNUAL',
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'reason' => 'Đi du lịch gia đình',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.leave_type', 'ANNUAL');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->staff->id,
            'leave_type' => 'ANNUAL',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'status' => 'PENDING',
        ]);
    }

    public function test_cannot_create_leave_request_with_invalid_dates()
    {
        $response = $this->actingAs($this->staff, 'api')
            ->postJson('/api/leave-requests', [
                'leave_type' => 'ANNUAL',
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-10', // End date is before start date
                'reason' => 'Nghỉ phép',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422)
            ->assertJsonStructure(['messages']);
    }

    public function test_cannot_create_overlapping_leave_request()
    {
        // First request
        LeaveRequest::create([
            'employee_id' => $this->staff->id,
            'leave_type' => 'ANNUAL',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'status' => 'PENDING',
        ]);

        // Overlapping request
        $response = $this->actingAs($this->staff, 'api')
            ->postJson('/api/leave-requests', [
                'leave_type' => 'SICK',
                'start_date' => '2026-06-11',
                'end_date' => '2026-06-14',
                'reason' => 'Bị ốm',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', 'LVE_002');
    }

    public function test_employee_can_list_own_leave_requests()
    {
        LeaveRequest::create([
            'employee_id' => $this->staff->id,
            'leave_type' => 'ANNUAL',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($this->staff, 'api')
            ->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_manager_can_list_pending_leave_requests()
    {
        LeaveRequest::create([
            'employee_id' => $this->staff->id,
            'leave_type' => 'ANNUAL',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/leave-requests/pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee.full_name', 'Nguyễn Văn Nhân Viên');
    }

    public function test_staff_cannot_list_pending_leave_requests()
    {
        $response = $this->actingAs($this->staff, 'api')
            ->getJson('/api/leave-requests/pending');

        $response->assertStatus(403)
            ->assertJsonPath('messages.error_code', 'EMP_005');
    }

    public function test_manager_can_approve_leave_request()
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->staff->id,
            'leave_type' => 'ANNUAL',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson("/api/leave-requests/{$leaveRequest->id}/approve", [
                'status' => 'APPROVED',
                'approver_note' => 'Đồng ý cho nghỉ',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.approver_note', 'Đồng ý cho nghỉ');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'APPROVED',
            'approved_by' => $this->manager->id,
            'approver_note' => 'Đồng ý cho nghỉ',
        ]);
    }

    public function test_manager_can_reject_leave_request()
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->staff->id,
            'leave_type' => 'ANNUAL',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson("/api/leave-requests/{$leaveRequest->id}/approve", [
                'status' => 'REJECTED',
                'approver_note' => 'Dự án đang gấp, không thể nghỉ phép',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'REJECTED')
            ->assertJsonPath('data.approver_note', 'Dự án đang gấp, không thể nghỉ phép');
    }

    public function test_cannot_approve_already_processed_leave_request()
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->staff->id,
            'leave_type' => 'ANNUAL',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson("/api/leave-requests/{$leaveRequest->id}/approve", [
                'status' => 'REJECTED',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', 'LVE_004');
    }
}
