<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Quotation\SubmitQuotationRequest;
use App\Http\Resources\User\Job\WorkerJobResource;
use App\Http\Resources\User\Quotation\QuotationResource;
use App\Services\User\JobService;
use App\Services\User\QuotationService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class WorkerJobController extends Controller
{
    protected $jobService;

    protected $quotationService;

    public function __construct(
        JobService $jobService,
        QuotationService $quotationService
    ) {
        $this->jobService = $jobService;
        $this->quotationService = $quotationService;
    }

    /**
     * List available jobs for worker
     * GET /worker/jobs/available
     */
    public function availableJobs(Request $request)
    {
        $worker = $request->user();
        $filters = $request->all();

        $jobs = $this->jobService->listAvailableJobs($worker, $filters);

        return Response::pagination(
            WorkerJobResource::collection($jobs),
            $jobs->total(),
            $jobs->currentPage(),
            $jobs->perPage(),
        );
    }

    /**
     * List worker's jobs (quoted or assigned)
     * GET /worker/jobs
     */
    public function index(Request $request)
    {
        $worker = $request->user();
        $filters = $request->all();

        $jobs = $this->jobService->listWorkerJobs($worker->id, $filters);

        return Response::pagination(
            WorkerJobResource::collection($jobs),
            $jobs->total(),
            $jobs->currentPage(),
            $jobs->perPage(),
        );
    }

    /**
     * View job detail
     * GET /worker/jobs/{id}
     */
    public function show($id, Request $request)
    {
        $worker = $request->user();
        $job = $this->jobService->getWorkerJobDetail($id, $worker->id);

        return Response::success((new WorkerJobResource($job))->resolve());
    }

    /**
     * Submit quotation for a job
     * POST /worker/jobs/{id}/quotation
     */
    public function submitQuotation($id, SubmitQuotationRequest $request)
    {
        $worker = $request->user();

        $quotation = $this->quotationService->submitQuotation(
            $id,
            $worker->id,
            $request->validated(),
            $worker->code
        );

        return Response::created((new QuotationResource($quotation))->resolve());
    }

    /**
     * Start working on job
     * POST /worker/jobs/{id}/start
     */
    public function start($id, Request $request)
    {
        $worker = $request->user();
        $job = $this->jobService->startJob($id, $worker->id);

        return Response::success((new WorkerJobResource($job))->resolve());
    }

    /**
     * Mark job as complete
     * POST /worker/jobs/{id}/complete
     */
    public function complete($id, Request $request)
    {
        $worker = $request->user();
        $job = $this->jobService->completeJob($id, $worker->id);

        return Response::success((new WorkerJobResource($job))->resolve());
    }

    /**
     * Worker rejects a job
     * POST /worker/jobs/{id}/reject
     */
    public function reject($id, Request $request)
    {
        $worker = $request->user();
        $this->jobService->rejectJob($id, $worker->id);

        return Response::success(['success' => true]);
    }

    /**
     * Worker replies to a complaint
     * POST /worker/jobs/{id}/complaints/{complaintId}/reply
     */
    public function replyComplaint($id, $complaintId, \App\Http\Requests\User\Job\ReplyComplaintRequest $request)
    {
        $worker = $request->user();
        $mediaCodes = $request->input('media_codes', []);

        $this->jobService->replyComplaint($id, $complaintId, $request->validated(), $mediaCodes, $worker->id);

        return Response::success(['success' => true]);
    }
}
