<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Job\CancelJobRequest;
use App\Http\Requests\User\Job\CreateJobRequest;
use App\Http\Resources\User\Job\JobResource;
use App\Http\Resources\User\Quotation\QuotationResource;
use App\Http\Resources\User\WorkerProfile\PublicWorkerProfileResource;
use App\Services\User\JobService;
use App\Services\User\QuotationService;
use App\Services\User\WorkerProfileService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class JobController extends Controller
{
    protected $jobService;

    protected $quotationService;

    protected $workerProfileService;

    public function __construct(
        JobService $jobService,
        QuotationService $quotationService,
        WorkerProfileService $workerProfileService
    ) {
        $this->jobService = $jobService;
        $this->quotationService = $quotationService;
        $this->workerProfileService = $workerProfileService;
    }

    public function store(CreateJobRequest $request)
    {
        $user = $request->user();
        $mediaCodes = $request->input('media_codes', []);

        $job = $this->jobService->createJob($request->validated(), $mediaCodes, $user);

        $job->load(['serviceCategory.parent', 'area', 'media', 'worker.workerProfile']);

        return Response::created((new JobResource($job))->resolve());
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->all();

        $jobs = $this->jobService->listCustomerJobs($user->id, $filters);

        return Response::pagination(
            JobResource::collection($jobs),
            $jobs->total(),
            $jobs->currentPage(),
            $jobs->perPage(),
        );
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $job = $this->jobService->getJobDetail($id, $user->id);

        return Response::success((new JobResource($job))->resolve());
    }

    public function cancel($id, CancelJobRequest $request)
    {
        $user = $request->user();
        $this->jobService->cancelJob($id, $user->id, $request->input('reason'));

        return Response::success(['message' => 'Job cancelled successfully']);
    }

    /**
     * List quotations for a job
     * GET /customer/jobs/{id}/quotations
     */
    public function quotations($id, Request $request)
    {
        $user = $request->user();
        $filters = $request->all();
        $quotations = $this->quotationService->listQuotations($id, $user->id, $filters);

        return Response::success(QuotationResource::collection($quotations)->resolve());
    }

    /**
     * Accept a quotation
     * POST /customer/jobs/{id}/quotations/{quotationId}/accept
     */
    public function acceptQuotation($id, $quotationId, Request $request)
    {
        $user = $request->user();
        $job = $this->quotationService->acceptQuotation($id, $quotationId, $user->id);

        return Response::success((new JobResource($job))->resolve());
    }

    /**
     * Reject a quotation
     * POST /customer/jobs/{id}/quotations/{quotationId}/reject
     */
    public function rejectQuotation($id, $quotationId, Request $request)
    {
        $user = $request->user();
        $quotation = $this->quotationService->rejectQuotation($id, $quotationId, $user->id);

        return Response::success((new QuotationResource($quotation))->resolve());
    }

    /**
     * Customer submits a complaint
     * POST /customer/jobs/{id}/complaint
     */
    public function submitComplaint($id, \App\Http\Requests\User\Job\SubmitComplaintRequest $request)
    {
        $user = $request->user();
        $mediaCodes = $request->input('media_codes', []);

        $complaint = $this->jobService->submitComplaint($id, $request->validated(), $mediaCodes, $user);

        return Response::success((new \App\Http\Resources\User\Complaint\ComplaintResource($complaint))->resolve());
    }

    /**
     * Customer views public worker profile
     * GET /customer/workers/{id}
     */
    public function showWorkerProfile($id, Request $request)
    {
        $user = $request->user();
        $profile = $this->workerProfileService->getPublicProfile((int) $id, $user->id);

        return Response::success((new PublicWorkerProfileResource($profile))->resolve());
    }

    /**
     * Customer reviews worker
     * POST /customer/jobs/{id}/review
     */
    public function reviewWorker($id, \App\Http\Requests\User\Job\ReviewWorkerRequest $request)
    {
        $user = $request->user();
        $this->jobService->reviewWorker($id, $request->validated(), $user->id);

        return Response::success(['success' => true]);
    }
}
