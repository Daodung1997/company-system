<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Review\CreateReviewRequest;
use App\Http\Resources\User\Review\ReviewResource;
use App\Services\User\ReviewService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected $service;

    public function __construct(ReviewService $service)
    {
        $this->service = $service;
    }

    public function create(CreateReviewRequest $request)
    {
        $data = $request->validated();
        $review = $this->service->createReview($data, $request->user());

        return Response::success((new ReviewResource($review))->resolve());
    }

    public function listMyReviews(Request $request)
    {
        $workerId = $request->user()->id;
        $reviews = $this->service->getWorkerReviews($workerId, $request->all());

        return Response::success(ReviewResource::collection($reviews)->resolve());
    }

    public function summary(Request $request)
    {
        $workerId = $request->user()->id;
        $summary = $this->service->getReviewSummary($workerId);

        return Response::success($summary);
    }

    public function listPublicWorkerReviews($workerId, Request $request)
    {
        $reviews = $this->service->getWorkerReviews($workerId, $request->all());

        return Response::success(ReviewResource::collection($reviews)->resolve());
    }
}
