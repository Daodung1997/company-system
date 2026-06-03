<?php

namespace App\Services\Employee;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Employee\EmployeeStatusConst;
use App\Exceptions\BusinessException;
use App\Repositories\Criteria\Employee\SortAndFilterEmployeeCriteria;
use App\Repositories\Employee\EmployeeRelativeRepository;
use App\Repositories\Employee\EmployeeRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Hash;

class EmployeeService extends AbstractService
{
    public function __construct(
        protected EmployeeRepository $employeeRepository,
        protected EmployeeRelativeRepository $relativeRepository
    ) {}

    /**
     * List employees with pagination, filters, sorting, search.
     */
    public function list(array $params)
    {
        $page = $params['page'] ?? 1;
        $limit = $params['per_page'] ?? 15;
        $filters = $params['filters'] ?? [];
        $sorts = $params['sorts'] ?? [];
        $search = $params['search'] ?? [];

        return $this->employeeRepository
            ->pushCriteria(new SortAndFilterEmployeeCriteria($filters, $sorts, $search))
            ->paginate($limit);
    }

    /**
     * Get employee detail with relationships.
     */
    public function show(int $id)
    {
        $employee = $this->employeeRepository
            ->with(['jobTitle', 'department', 'relatives', 'contracts.documents', 'documents', 'workHistories.department', 'workHistories.jobTitle'])
            ->find($id);

        if (!$employee) {
            throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
        }

        return $employee;
    }

    /**
     * Create a new employee.
     */
    public function create(array $data)
    {
        $this->beginTransaction();
        try {
            // Extract relatives
            $relativesData = $data['relatives'] ?? [];
            unset($data['relatives']);

            // Extract work histories
            $workHistoriesData = $data['work_histories'] ?? [];
            unset($data['work_histories']);

            // Set default password and force password change on first login
            if (!isset($data['password']) || empty($data['password'])) {
                $data['password'] = 'P@ssw0rd123';
            }
            $data['password'] = Hash::make($data['password']);
            $data['must_change_password'] = true;

            $employee = $this->employeeRepository->create($data);

            if (!empty($relativesData)) {
                foreach ($relativesData as $relativeItem) {
                    $relativeItem['employee_id'] = $employee->id;
                    $this->relativeRepository->create($relativeItem);
                }
                // Sync dependents_count
                $this->syncDependentsCount($employee->id);
            }

            if (empty($workHistoriesData)) {
                $employee->workHistories()->create([
                    'department_id' => $employee->department_id,
                    'job_title_id' => $employee->job_title_id,
                    'start_date' => $employee->join_date,
                    'salary' => null,
                    'note' => 'Ngày làm việc đầu tiên',
                ]);
            } else {
                foreach ($workHistoriesData as $historyItem) {
                    $employee->workHistories()->create($historyItem);
                }
            }

            $employee->load(['jobTitle', 'department', 'relatives', 'workHistories.department', 'workHistories.jobTitle']);

            $this->commitTransaction();

            return $employee;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Update an existing employee.
     */
    public function update(int $id, array $data)
    {
        $this->beginTransaction();
        try {
            $employee = $this->employeeRepository->find($id);

            if (!$employee) {
                throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
            }

            // Extract relatives
            $relativesData = $data['relatives'] ?? null;
            unset($data['relatives']);

            // Extract work histories
            $workHistoriesData = $data['work_histories'] ?? null;
            unset($data['work_histories']);

            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $this->employeeRepository->update($id, $data);

            if ($relativesData !== null) {
                $keepIds = [];
                foreach ($relativesData as $relativeItem) {
                    if (isset($relativeItem['id']) && !empty($relativeItem['id'])) {
                        // Update existing relative
                        $this->relativeRepository->update($relativeItem['id'], $relativeItem);
                        $keepIds[] = (int) $relativeItem['id'];
                    } else {
                        // Create new relative
                        $relativeItem['employee_id'] = $id;
                        $newRelative = $this->relativeRepository->create($relativeItem);
                        $keepIds[] = $newRelative->id;
                    }
                }

                // Delete any relatives belonging to this employee that are not in the payload
                $existingRelatives = $this->relativeRepository->getByEmployeeId($id);
                foreach ($existingRelatives as $existingRelative) {
                    if (!in_array($existingRelative->id, $keepIds)) {
                        $this->relativeRepository->deleteWhere(['id' => $existingRelative->id]);
                    }
                }

                // Sync dependents count
                $this->syncDependentsCount($id);
            }

            if ($workHistoriesData !== null) {
                $keepHistoryIds = [];
                foreach ($workHistoriesData as $historyItem) {
                    if (isset($historyItem['id']) && !empty($historyItem['id'])) {
                        // Update existing history item
                        $employee->workHistories()->where('id', $historyItem['id'])->update([
                            'department_id' => $historyItem['department_id'],
                            'job_title_id' => $historyItem['job_title_id'],
                            'start_date' => $historyItem['start_date'],
                            'end_date' => $historyItem['end_date'] ?? null,
                            'salary' => $historyItem['salary'] ?? null,
                            'note' => $historyItem['note'] ?? null,
                        ]);
                        $keepHistoryIds[] = (int) $historyItem['id'];
                    } else {
                        // Create new history item
                        $newHistory = $employee->workHistories()->create($historyItem);
                        $keepHistoryIds[] = $newHistory->id;
                    }
                }

                // Delete removed histories
                $employee->workHistories()->whereNotIn('id', $keepHistoryIds)->delete();
            }

            $employee = $this->employeeRepository
                ->with(['jobTitle', 'department', 'relatives', 'workHistories.department', 'workHistories.jobTitle'])
                ->find($id);

            $this->commitTransaction();

            return $employee;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Soft delete employee by setting status to INACTIVE.
     */
    public function delete(int $id)
    {
        $currentUser = auth('api')->user();

        if ($currentUser && $currentUser->id === $id) {
            throw new BusinessException(ExceptionCode::EMPLOYEE_CANNOT_DELETE_SELF, 'Cannot delete your own account', 400);
        }

        $employee = $this->employeeRepository->find($id);

        if (!$employee) {
            throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
        }

        $this->employeeRepository->update($id, ['status' => EmployeeStatusConst::INACTIVE]);

        return true;
    }

    /**
     * Get relatives for a specific employee.
     */
    public function getRelatives(int $employeeId)
    {
        $employee = $this->employeeRepository->find($employeeId);

        if (!$employee) {
            throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
        }

        return $this->relativeRepository->getByEmployeeId($employeeId);
    }

    /**
     * Create a relative for an employee.
     */
    public function createRelative(int $employeeId, array $data)
    {
        $this->beginTransaction();
        try {
            $employee = $this->employeeRepository->find($employeeId);

            if (!$employee) {
                throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
            }

            $data['employee_id'] = $employeeId;
            $relative = $this->relativeRepository->create($data);

            // Sync dependents_count
            if ($data['is_dependent'] ?? false) {
                $this->syncDependentsCount($employeeId);
            }

            $this->commitTransaction();

            return $relative;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Update a relative.
     */
    public function updateRelative(int $employeeId, int $relativeId, array $data)
    {
        $this->beginTransaction();
        try {
            $employee = $this->employeeRepository->find($employeeId);

            if (!$employee) {
                throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
            }

            $relative = $this->relativeRepository
                ->findWhere(['employee_id' => $employeeId, 'id' => $relativeId])
                ->first();

            if (!$relative) {
                throw new BusinessException(ExceptionCode::EMPLOYEE_RELATIVE_NOT_FOUND, 'Relative not found', 404);
            }

            $this->relativeRepository->update($relativeId, $data);

            // Sync dependents_count
            $this->syncDependentsCount($employeeId);

            $relative = $this->relativeRepository->find($relativeId);

            $this->commitTransaction();

            return $relative;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Delete a relative.
     */
    public function deleteRelative(int $employeeId, int $relativeId)
    {
        $this->beginTransaction();
        try {
            $employee = $this->employeeRepository->find($employeeId);

            if (!$employee) {
                throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
            }

            $relative = $this->relativeRepository
                ->findWhere(['employee_id' => $employeeId, 'id' => $relativeId])
                ->first();

            if (!$relative) {
                throw new BusinessException(ExceptionCode::EMPLOYEE_RELATIVE_NOT_FOUND, 'Relative not found', 404);
            }

            $this->relativeRepository->deleteWhere(['id' => $relativeId, 'employee_id' => $employeeId]);

            // Sync dependents_count
            $this->syncDependentsCount($employeeId);

            $this->commitTransaction();

            return true;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Sync dependents_count on the Employee model.
     */
    private function syncDependentsCount(int $employeeId): void
    {
        $count = $this->relativeRepository->countDependents($employeeId);
        $this->employeeRepository->update($employeeId, ['dependents_count' => $count]);
    }

    /**
     * Upload personal document for employee.
     */
    public function uploadDocument(int $employeeId, \Illuminate\Http\UploadedFile $file, ?string $title = null)
    {
        $employee = $this->employeeRepository->find($employeeId);
        if (!$employee) {
            throw new BusinessException(ExceptionCode::EMPLOYEE_NOT_FOUND, 'Employee not found', 404);
        }

        /** @var \App\Services\Document\DocumentUploadService $uploadService */
        $uploadService = app(\App\Services\Document\DocumentUploadService::class);
        $document = $uploadService->upload($file, 'public', \App\Models\Employee::class, $employeeId);

        if ($title) {
            $document->update(['origin_name' => $title]);
        }

        return $document;
    }
}
