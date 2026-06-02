<?php

namespace Tests\Feature\Document;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Contract;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
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

        Storage::fake('public');

        // Seed Department
        $this->department = Department::create([
            'name' => 'IT Department',
            'description' => 'IT Department',
        ]);

        // Seed Employee (Staff)
        $this->employee = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Nguyễn Nhân Viên',
            'email' => 'employee@compliance.vn',
            'phone' => '0987654323',
            'password' => Hash::make('password123'),
            'role' => 'STAFF',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        // Seed Manager (Manager)
        $this->manager = Employee::create([
            'department_id' => $this->department->id,
            'full_name' => 'Trần Quản Lý',
            'email' => 'manager@compliance.vn',
            'phone' => '0987654324',
            'password' => Hash::make('password123'),
            'role' => 'MANAGER',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
        ]);

        // Auth Tokens
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

    public function test_user_can_upload_document()
    {
        $file = UploadedFile::fake()->create('employment_contract.pdf', 500, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/documents/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'id',
                    'code',
                    'origin_name',
                    'file_path',
                    'extension',
                    'filesize',
                    'url',
                ]
            ]);

        $this->assertDatabaseHas('t_documents', [
            'origin_name' => 'employment_contract.pdf',
            'extension' => 'pdf',
        ]);
    }

    public function test_upload_fails_for_invalid_file_extension()
    {
        $file = UploadedFile::fake()->create('malicious_script.sh', 10, 'text/x-shellscript');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/documents/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', 'DOC_002');
    }

    public function test_upload_fails_for_excessive_file_size()
    {
        // 11MB file
        $file = UploadedFile::fake()->create('large_scan.png', 11 * 1024, 'image/png');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/documents/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', 'DOC_003');
    }

    public function test_user_can_attach_document_to_contract()
    {
        // Create a contract
        $contract = Contract::create([
            'employee_id' => $this->employee->id,
            'contract_code' => 'HDLD-EDM-TEST',
            'type' => 'LABOR',
            'sign_date' => '2026-06-01',
            'start_date' => '2026-06-01',
            'status' => 'ACTIVE',
        ]);

        // Upload a document first
        $file = UploadedFile::fake()->create('contract_signed.pdf', 500, 'application/pdf');
        $document = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/documents/upload', [
                'file' => $file,
            ])->json('data');

        // Attach document to contract
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/documents/attach', [
                'document_id' => $document['id'],
                'documentable_type' => Contract::class,
                'documentable_id' => $contract->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.contract_id', $contract->id)
            ->assertJsonPath('data.employee_id', $this->employee->id); // Auto matched employee_id!

        $this->assertDatabaseHas('t_documents', [
            'id' => $document['id'],
            'contract_id' => $contract->id,
            'employee_id' => $this->employee->id,
        ]);
    }

    public function test_user_can_download_document_securely()
    {
        // Upload a document
        $file = UploadedFile::fake()->create('contract_signed.pdf', 500, 'application/pdf');
        $uploadResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/documents/upload', [
                'file' => $file,
            ]);
        $documentId = $uploadResponse->json('data.id');

        // Download document
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/documents/' . $documentId . '/download');

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename=contract_signed.pdf');
    }

    public function test_user_can_delete_document()
    {
        // Upload a document
        $file = UploadedFile::fake()->create('contract_signed.pdf', 500, 'application/pdf');
        $uploadResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/documents/upload', [
                'file' => $file,
            ]);
        $documentId = $uploadResponse->json('data.id');

        // Delete document
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/documents/' . $documentId);

        $response->assertStatus(200);

        $this->assertSoftDeleted('t_documents', [
            'id' => $documentId,
        ]);
    }
}
