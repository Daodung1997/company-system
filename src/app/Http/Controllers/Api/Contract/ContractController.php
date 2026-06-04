<?php

namespace App\Http\Controllers\Api\Contract;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Resources\Contract\ContractResource;
use App\Services\Contract\ContractService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(protected ContractService $contractService) {}

    /**
     * GET /api/contracts - List company contracts with filters
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            throw new \App\Exceptions\ForbiddenException('auth.forbidden', 403);
        }

        $filters = $request->only(['q', 'type', 'status', 'employee_id']);

        // Staff can only view their own contracts
        if (!$user->hasPermissionTo('view-contracts')) {
            $filters['employee_id'] = $user->id;
        }

        $contracts = $this->contractService->list($filters);

        return Response::success(ContractResource::collection($contracts)->resolve());
    }

    /**
     * GET /api/contracts/{id} - Show details of a contract
     */
    public function show(int $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            throw new \App\Exceptions\ForbiddenException('auth.forbidden', 403);
        }

        $contract = $this->contractService->show($id);

        // Staff can only see their own contract
        if (!$user->hasPermissionTo('view-contracts') && $contract->employee_id !== $user->id) {
            throw new \App\Exceptions\ForbiddenException('auth.forbidden', 403);
        }

        return Response::success((new ContractResource($contract))->resolve());
    }

    /**
     * GET /api/contracts/{id}/export-pdf - Export contract details to PDF
     */
    public function exportPdf(int $id, Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            throw new \App\Exceptions\ForbiddenException('auth.forbidden', 403);
        }

        $contract = $this->contractService->show($id);

        // Staff can only export their own contract
        if (!$user->hasPermissionTo('view-contracts') && $contract->employee_id !== $user->id) {
            throw new \App\Exceptions\ForbiddenException('auth.forbidden', 403);
        }

        $theme = $request->query('theme', 'classic');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.contract', compact('contract', 'theme'));

        return $pdf->download("Hop_dong_{$contract->contract_code}.pdf");
    }

    /**
     * POST /api/contracts - Create a new contract
     */
    public function store(StoreContractRequest $request)
    {
        $user = auth('api')->user();
        if (!$user || !$user->hasPermissionTo('create-contracts')) {
            throw new \App\Exceptions\ForbiddenException('auth.forbidden', 403);
        }

        $contract = $this->contractService->create($request->validated());

        return Response::success((new ContractResource($contract))->resolve());
    }

    /**
     * PUT /api/contracts/{id} - Update an existing contract
     */
    public function update(int $id, StoreContractRequest $request)
    {
        $user = auth('api')->user();
        if (!$user || !$user->hasPermissionTo('update-contracts')) {
            throw new \App\Exceptions\ForbiddenException('auth.forbidden', 403);
        }

        $contract = $this->contractService->update($id, $request->validated());

        return Response::success((new ContractResource($contract))->resolve());
    }

    /**
     * DELETE /api/contracts/{id} - Delete a contract
     */
    public function destroy(int $id)
    {
        $user = auth('api')->user();
        // Only admin/manager can delete contracts
        if (!$user || (!$user->hasPermissionTo('delete-contracts') && $user->role !== 'ADMIN' && $user->role !== 'MANAGER')) {
            throw new \App\Exceptions\ForbiddenException('auth.forbidden', 403);
        }

        $this->contractService->delete($id);

        return Response::success(['message' => 'Contract deleted successfully']);
    }
}
