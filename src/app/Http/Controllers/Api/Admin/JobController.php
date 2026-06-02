<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Job\AddJobNoteRequest;
use App\Http\Resources\Admin\Job\AdminJobDetailResource;
use App\Http\Resources\Admin\Job\AdminJobListResource;
use App\Http\Resources\Admin\Job\AdminJobNoteResource;
use App\Services\Admin\JobService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class JobController extends Controller
{
    protected $jobService;

    public function __construct(JobService $jobService)
    {
        $this->jobService = $jobService;
    }

    public function index(Request $request)
    {
        $jobs = $this->jobService->listJobs($request->all());

        return Response::pagination(
            AdminJobListResource::collection($jobs),
            $jobs->total(),
            $jobs->currentPage(),
            $jobs->perPage(),
        );
    }

    public function show($id)
    {
        $job = $this->jobService->getJobDetail($id);

        return Response::success((new AdminJobDetailResource($job))->resolve());
    }

    public function resolveComplete($id, Request $request)
    {
        $job = $this->jobService->resolveComplete($id, $request->user()->id);

        return Response::success((new AdminJobDetailResource($job))->resolve());
    }

    public function resolveRefund($id, Request $request)
    {
        $job = $this->jobService->resolveRefund($id, $request->user()->id);

        return Response::success((new AdminJobDetailResource($job))->resolve());
    }

    public function addNote($id, AddJobNoteRequest $request)
    {
        $note = $this->jobService->addJobNote($id, $request->user()->id, $request->validated()['note']);

        return Response::created((new AdminJobNoteResource($note))->resolve());
    }

    public function listNotes($id)
    {
        $notes = $this->jobService->listJobNotes($id);

        return Response::success(AdminJobNoteResource::collection($notes)->resolve());
    }
}
