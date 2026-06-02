<?php

namespace App\Http\Controllers\Api\Compliance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Compliance\ComplianceIssueResource;
use App\Services\Compliance\ComplianceService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function __construct(protected ComplianceService $complianceService) {}

    /**
     * GET /api/compliance - List all active issues with filters
     */
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'issue_type', 'severity', 'status']);
        $issues = $this->complianceService->list($filters);

        return Response::success(ComplianceIssueResource::collection($issues)->resolve());
    }

    /**
     * POST /api/compliance/scan - Trigger a realtime compliance scan for current company
     */
    public function scan()
    {
        $employee = auth('api')->user();
        if (!$employee) {
            return Response::error('Unauthenticated', 401);
        }

        $results = $this->complianceService->runScan();

        return Response::success($results, 'Bộ máy kiểm soát đã hoàn thành quét tuân thủ thời gian thực.');
    }

    /**
     * PUT /api/compliance/{id}/resolve - Manually resolve a compliance issue
     */
    public function resolve(int $id, Request $request)
    {
        $request->validate([
            'note' => 'nullable|string|max:255',
        ]);

        $issue = $this->complianceService->resolveIssue($id, $request->get('note', ''));

        if (!$issue) {
            return Response::error('Cảnh báo vi phạm không tồn tại hoặc không thuộc quyền quản trị của bạn.', 404);
        }

        return Response::success(new ComplianceIssueResource($issue), 'Đã giải quyết và loại bỏ cảnh báo vi phạm.');
    }
}
