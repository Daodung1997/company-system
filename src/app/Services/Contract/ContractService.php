<?php

namespace App\Services\Contract;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Contract\ContractRepository;
use App\Repositories\Employee\EmployeeRepository;
use App\Services\AbstractService;

class ContractService extends AbstractService
{
    public function __construct(
        protected ContractRepository $contractRepository,
        protected EmployeeRepository $employeeRepository
    ) {}

    /**
     * Get contracts list under the user's company with filters.
     */
    public function list(array $filters)
    {
        $employee = auth('api')->user();
        if (!$employee) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        return $this->contractRepository->getContracts($filters);
    }

    /**
     * Get a specific contract.
     */
    public function show(int $id)
    {
        $employee = auth('api')->user();
        if (!$employee) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        $contract = $this->contractRepository->with(['employee', 'documents'])->find($id);

        if (!$contract) {
            throw new BusinessException(ExceptionCode::CONTRACT_NOT_FOUND, 'Contract not found', 404);
        }

        return $contract;
    }

    /**
     * Create a new contract.
     */
    public function create(array $data)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        $data = $this->sanitizeContractData($data);

        // 1. Verify employee exists
        if (!empty($data['employee_id'])) {
            $targetEmployee = $this->employeeRepository->find($data['employee_id']);
            if (!$targetEmployee) {
                throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
            }
        }

        // 2. Validate contract code uniqueness
        if (!empty($data['contract_code'])) {
            $existing = $this->contractRepository->findWhere(['contract_code' => $data['contract_code']])->first();
            if ($existing) {
                throw new BusinessException(ExceptionCode::CONTRACT_CODE_ALREADY_EXISTS, 'Contract code already exists', 400);
            }
        } else {
            // Generate standard unique contract code
            $year = date('Y');
            $prefix = $data['type'] === 'LABOR' ? 'HDLD' : ($data['type'] === 'VENDOR' ? 'HDCP' : 'HDDT');
            $count = $this->contractRepository->count() + 1;
            $data['contract_code'] = sprintf('%s-%s-%04d', $prefix, $year, $count);
        }

        // 3. Prevent overlapping active labor contracts for the same employee
        if ($data['type'] === 'LABOR' && !empty($data['employee_id']) && ($data['status'] ?? 'ACTIVE') === 'ACTIVE') {
            $isOverlapped = $this->contractRepository->hasOverlappingActiveContract(
                $data['employee_id'],
                $data['start_date'],
                $data['end_date'] ?? null
            );
            if ($isOverlapped) {
                throw new BusinessException(
                    ExceptionCode::CONTRACT_OVERLAP,
                    'Employee already has an active labor contract that overlaps with this period',
                    400
                );
            }
        }

        $data['created_by'] = $currentUser->full_name;

        $contract = $this->contractRepository->create($data);

        // Auto generate and attach PDF document
        try {
            $renderedContract = $this->contractRepository->with(['employee'])->find($contract->id);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.contract', ['contract' => $renderedContract, 'theme' => 'classic']);
            $pdfContent = $pdf->output();

            $fileName = 'Hop_dong_' . $contract->contract_code . '.pdf';
            $encryptedName = time() . '_' . \Illuminate\Support\Str::random(16) . '.pdf';
            $filePath = 'documents/' . $encryptedName;

            \Illuminate\Support\Facades\Storage::disk('public')->put($filePath, $pdfContent);

            \App\Models\Document::create([
                'origin_name' => $fileName,
                'file_path' => $filePath,
                'disk' => 'public',
                'extension' => 'pdf',
                'filesize' => strlen($pdfContent),
                'documentable_id' => $contract->id,
                'documentable_type' => \App\Models\Contract::class,
                'employee_id' => $contract->employee_id,
                'contract_id' => $contract->id,
                'status' => 'in_use',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to auto-generate contract PDF: ' . $e->getMessage());
        }

        return $this->contractRepository->with(['employee', 'documents'])->find($contract->id);
    }

    /**
     * Update an existing contract.
     */
    public function update(int $id, array $data)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        $contract = $this->contractRepository->find($id);
        if (!$contract) {
            throw new BusinessException(ExceptionCode::CONTRACT_NOT_FOUND, 'Contract not found', 404);
        }

        $data = $this->sanitizeContractData($data, $contract->type);

        // 1. Validate employee
        if (!empty($data['employee_id'])) {
            $targetEmployee = $this->employeeRepository->find($data['employee_id']);
            if (!$targetEmployee) {
                throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
            }
        }

        // 2. Validate contract code uniqueness
        if (!empty($data['contract_code']) && $data['contract_code'] !== $contract->contract_code) {
            $existing = $this->contractRepository->findWhere(['contract_code' => $data['contract_code']])->first();
            if ($existing && $existing->id !== $id) {
                throw new BusinessException(ExceptionCode::CONTRACT_CODE_ALREADY_EXISTS, 'Contract code already exists', 400);
            }
        }

        // 3. Prevent overlapping active labor contracts
        $employeeId = $data['employee_id'] ?? $contract->employee_id;
        $type = $data['type'] ?? $contract->type;
        $status = $data['status'] ?? $contract->status;
        $startDate = $data['start_date'] ?? $contract->start_date->format('Y-m-d');
        $endDate = array_key_exists('end_date', $data) ? $data['end_date'] : ($contract->end_date ? $contract->end_date->format('Y-m-d') : null);

        if ($type === 'LABOR' && !empty($employeeId) && $status === 'ACTIVE') {
            $isOverlapped = $this->contractRepository->hasOverlappingActiveContract(
                $employeeId,
                $startDate,
                $endDate,
                $id
            );
            if ($isOverlapped) {
                throw new BusinessException(
                    ExceptionCode::CONTRACT_OVERLAP,
                    'Employee already has an active labor contract that overlaps with this period',
                    400
                );
            }
        }

        $data['updated_by'] = $currentUser->full_name;

        $this->contractRepository->update($id, $data);

        return $this->contractRepository->with(['employee', 'documents'])->find($id);
    }

    /**
     * Delete a contract.
     */
    public function delete(int $id)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        $contract = $this->contractRepository->find($id);
        if (!$contract) {
            throw new BusinessException(ExceptionCode::CONTRACT_NOT_FOUND, 'Contract not found', 404);
        }

        return $contract->delete();
    }

    /**
     * Sanitize contract data fields based on its type to prevent database integrity constraints.
     */
    protected function sanitizeContractData(array $data, ?string $currentType = null): array
    {
        $type = $data['type'] ?? $currentType;

        if ($type !== 'LABOR') {
            // Nullify labor-specific fields that are nullable
            $data['employee_id'] = null;
            $data['employment_type'] = null;
            $data['job_title'] = null;
            $data['work_location'] = null;
            $data['bank_name'] = null;
            $data['bank_account_number'] = null;
            $data['insurance_enrolled'] = null;

            // Set default numeric/boolean values for fields that are not nullable
            $data['working_hours_per_day'] = 0;
            $data['probation_salary_percentage'] = 0;
            $data['included_overtime_hours'] = 0;
            $data['probation_period_months'] = 0;
            $data['is_36_agreement_applicable'] = false;
            $data['overtime_allowance_included'] = false;
        } else {
            // For LABOR contracts, fill not nullable numeric/boolean fields with defaults if null is passed
            if (array_key_exists('working_hours_per_day', $data) && $data['working_hours_per_day'] === null) {
                $data['working_hours_per_day'] = 8.00;
            }
            if (array_key_exists('probation_salary_percentage', $data) && $data['probation_salary_percentage'] === null) {
                $data['probation_salary_percentage'] = 85;
            }
            if (array_key_exists('included_overtime_hours', $data) && $data['included_overtime_hours'] === null) {
                $data['included_overtime_hours'] = 0;
            }
            if (array_key_exists('probation_period_months', $data) && $data['probation_period_months'] === null) {
                $data['probation_period_months'] = 0;
            }
            if (array_key_exists('is_36_agreement_applicable', $data) && $data['is_36_agreement_applicable'] === null) {
                $data['is_36_agreement_applicable'] = false;
            }
            if (array_key_exists('overtime_allowance_included', $data) && $data['overtime_allowance_included'] === null) {
                $data['overtime_allowance_included'] = false;
            }
        }

        return $data;
    }
}
