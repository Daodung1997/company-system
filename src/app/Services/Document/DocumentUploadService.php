<?php

namespace App\Services\Document;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Commons\File as FileConst;
use App\Exceptions\BusinessException;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Contract;
use App\Models\Transaction;
use App\Repositories\Document\DocumentRepository;
use App\Services\AbstractService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentUploadService extends AbstractService
{
    protected DocumentRepository $documentRepository;

    public function __construct(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * Upload and save a document.
     *
     * @param UploadedFile $file
     * @param string $disk
     * @param string|null $documentableType
     * @param int|null $documentableId
     * @return Document
     * @throws BusinessException
     */
    public function upload(UploadedFile $file, string $disk = 'public', ?string $documentableType = null, ?int $documentableId = null): Document
    {
        $this->validateFile($file);

        $originName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $filesize = $file->getSize();

        // Encrypt filename using timestamp and high randomness to avoid collisions and leaks
        $encryptedName = time() . '_' . Str::random(16) . '.' . $extension;
        $folder = 'documents';
        
        $filePath = $file->storeAs($folder, $encryptedName, $disk);

        if (!$filePath) {
            throw new BusinessException(ExceptionCode::INTERNAL_SERVER_ERROR, 'Failed to store file.', 500);
        }

        // Initialize foreign keys matching
        $employeeId = null;
        $contractId = null;
        $transactionId = null;

        // Auto mapping key columns if model matches
        if ($documentableType && $documentableId) {
            if ($documentableType === Employee::class || is_subclass_of($documentableType, Employee::class) || $documentableType === 'Employee') {
                $employeeId = $documentableId;
            } elseif ($documentableType === Contract::class || is_subclass_of($documentableType, Contract::class) || $documentableType === 'Contract') {
                $contractId = $documentableId;
                
                // If it is a contract, we can also extract employee_id from contract model if loaded/exists!
                $contract = Contract::find($documentableId);
                if ($contract) {
                    $employeeId = $contract->employee_id;
                }
            } elseif ($documentableType === Transaction::class || is_subclass_of($documentableType, Transaction::class) || strtolower($documentableType) === 'transaction' || $documentableType === 'App\\Models\\Transaction') {
                $transactionId = $documentableId;
            }
        }

        $data = [
            'origin_name' => $originName,
            'file_path' => $filePath,
            'disk' => $disk,
            'extension' => $extension,
            'filesize' => $filesize,
            'documentable_id' => $documentableId,
            'documentable_type' => $documentableType,
            'employee_id' => $employeeId,
            'contract_id' => $contractId,
            'transaction_id' => $transactionId,
            'status' => 'in_use', // active immediately on upload/attach
        ];

        $this->beginTransaction();
        try {
            $document = $this->documentRepository->create($data);
            $this->commitTransaction();
            return $document;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            Storage::disk($disk)->delete($filePath);
            throw $e;
        }
    }

    /**
     * Validate file type and size.
     *
     * @param UploadedFile $file
     * @throws BusinessException
     */
    protected function validateFile(UploadedFile $file): void
    {
        $allowedExtensions = ['pdf', 'docx', 'doc', 'jpeg', 'jpg', 'png', 'xlsx', 'xls'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            throw new BusinessException(
                ExceptionCode::DOCUMENT_INVALID_EXTENSION,
                'File type not allowed. Supported formats: PDF, Word, JPEG, PNG, Excel.',
                400
            );
        }

        // Limit maximum size to 10MB
        $maxSizeBytes = 10 * 1024 * 1024; // 10MB
        if ($file->getSize() > $maxSizeBytes) {
            throw new BusinessException(
                ExceptionCode::DOCUMENT_SIZE_EXCEEDED,
                'File size exceeds the 10MB limit.',
                400
            );
        }
    }

    /**
     * Attach an existing document to a polymorphic model.
     */
    public function attach(int $documentId, string $documentableType, int $documentableId): Document
    {
        $document = $this->documentRepository->find($documentId);
        if (!$document) {
            throw new BusinessException(ExceptionCode::DOCUMENT_NOT_FOUND, 'Document not found.', 404);
        }

        $employeeId = $document->employee_id;
        $contractId = $document->contract_id;
        $transactionId = $document->transaction_id;

        if ($documentableType === Employee::class || $documentableType === 'Employee') {
            $employeeId = $documentableId;
        } elseif ($documentableType === Contract::class || $documentableType === 'Contract') {
            $contractId = $documentableId;
            $contract = Contract::find($documentableId);
            if ($contract) {
                $employeeId = $contract->employee_id;
            }
        } elseif ($documentableType === Transaction::class || strtolower($documentableType) === 'transaction' || $documentableType === 'App\\Models\\Transaction') {
            $transactionId = $documentableId;
        }

        $this->beginTransaction();
        try {
            $this->documentRepository->update($documentId, [
                'documentable_id' => $documentableId,
                'documentable_type' => $documentableType,
                'employee_id' => $employeeId,
                'contract_id' => $contractId,
                'transaction_id' => $transactionId,
                'status' => 'in_use',
            ]);
            
            $updatedDocument = $this->documentRepository->find($documentId);
            $this->commitTransaction();
            return $updatedDocument;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Detach or delete a document.
     */
    public function delete(int $id): bool
    {
        $document = $this->documentRepository->find($id);
        if (!$document) {
            throw new BusinessException(ExceptionCode::DOCUMENT_NOT_FOUND, 'Document not found.', 404);
        }

        $this->beginTransaction();
        try {
            // Delete file from disk
            Storage::disk($document->disk)->delete($document->file_path);
            
            // Delete record
            $deleted = $document->delete();
            $this->commitTransaction();
            return (bool) $deleted;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
