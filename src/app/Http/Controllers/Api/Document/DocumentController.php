<?php

namespace App\Http\Controllers\Api\Document;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Http\Requests\Document\AttachDocumentRequest;
use App\Http\Resources\Document\DocumentResource;
use App\Services\Document\DocumentUploadService;
use App\Repositories\Document\DocumentRepository;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    protected DocumentUploadService $documentUploadService;
    protected DocumentRepository $documentRepository;

    public function __construct(
        DocumentUploadService $documentUploadService,
        DocumentRepository $documentRepository
    ) {
        $this->documentUploadService = $documentUploadService;
        $this->documentRepository = $documentRepository;
    }

    /**
     * GET /api/documents - Search and filter documents centrally
     */
    public function index(Request $request)
    {
        $query = \App\Models\Document::query()->with(['employee', 'contract']);

        // Search by keyword
        if ($request->has('keyword') && !empty($request->get('keyword'))) {
            $keyword = $request->get('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('origin_name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        // Filter by documentable_type
        if ($request->has('documentable_type') && !empty($request->get('documentable_type'))) {
            $type = $request->get('documentable_type');
            if ($type === 'employee') {
                $query->where('documentable_type', 'App\\Models\\Employee');
            } elseif ($type === 'contract') {
                $query->where('documentable_type', 'App\\Models\\Contract');
            } elseif ($type === 'transaction') {
                $query->where('documentable_type', 'App\\Models\\Transaction');
            } else {
                $query->where('documentable_type', $type);
            }
        }

        // Filter by extension
        if ($request->has('extension') && !empty($request->get('extension'))) {
            $query->where('extension', strtolower($request->get('extension')));
        }

        $query->orderBy('created_at', 'desc');

        $limit = $request->get('limit') ? (int) $request->get('limit') : 15;
        $documents = $query->paginate($limit);

        return Response::pagination(
            DocumentResource::collection($documents),
            $documents->total(),
            $documents->currentPage(),
            $documents->perPage()
        );
    }

    /**
     * POST /api/documents/upload - Upload a file securely
     */
    public function upload(UploadDocumentRequest $request)
    {
        $file = $request->file('file');
        $documentableType = $request->get('documentable_type');
        $documentableId = $request->get('documentable_id') ? (int) $request->get('documentable_id') : null;

        $document = $this->documentUploadService->upload($file, 'public', $documentableType, $documentableId);

        return Response::success((new DocumentResource($document))->resolve());
    }

    /**
     * POST /api/documents/attach - Attach an existing document to a polymorphic model
     */
    public function attach(AttachDocumentRequest $request)
    {
        $documentId = (int) $request->get('document_id');
        $documentableType = $request->get('documentable_type');
        $documentableId = (int) $request->get('documentable_id');

        $document = $this->documentUploadService->attach($documentId, $documentableType, $documentableId);

        return Response::success((new DocumentResource($document))->resolve());
    }

    /**
     * DELETE /api/documents/{id} - Delete and detach a document
     */
    public function destroy($id)
    {
        $this->documentUploadService->delete((int) $id);

        return Response::success(['message' => 'Tài liệu đã được xóa thành công.']);
    }

    /**
     * GET /api/documents/{id}/download - Secure download stream for documents
     */
    public function download($id)
    {
        $document = $this->documentRepository->find((int) $id);
        if (!$document) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::DOCUMENT_NOT_FOUND,
                'Không tìm thấy tài liệu.',
                404
            );
        }

        $currentUser = auth()->user();

        // Security boundaries: check if document belongs to employee/contract
        // (All authorized users in the single-tenant system can download)

        $filePath = $document->file_path;
        $disk = $document->disk;

        if (!Storage::disk($disk)->exists($filePath)) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::DOCUMENT_NOT_FOUND,
                'Tệp tin không tồn tại trên hệ thống lưu trữ.',
                404
            );
        }

        return Storage::disk($disk)->download($filePath, $document->origin_name);
    }
}
