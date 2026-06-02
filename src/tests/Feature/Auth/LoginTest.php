<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'Phòng IT',
            'description' => 'IT Department',
        ]);
    }

    public function test_employee_can_login_via_email()
    {
        $employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'admin@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin@compliance.vn',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'employee' => [
                        'id',
                        'code',
                        'full_name',
                        'email',
                        'phone',
                        'role',
                    ],
                ],
            ]);
    }

    public function test_employee_can_login_via_phone()
    {
        $employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'admin@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => '0987654321',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);
    }

    public function test_employee_cannot_login_with_incorrect_password()
    {
        $employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'admin@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin@compliance.vn',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
            ]);
    }

    public function test_employee_cannot_login_with_inactive_status()
    {
        $employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'admin@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'status' => 'INACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin@compliance.vn',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'code' => 403,
            ]);
    }

    public function test_employee_can_get_me()
    {
        $employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'admin@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'admin@compliance.vn',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.access_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'admin@compliance.vn');
    }

    public function test_employee_can_logout()
    {
        $employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'admin@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'admin@compliance.vn',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.access_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'message' => 'Đăng xuất thành công.',
                ],
            ]);
    }
}
