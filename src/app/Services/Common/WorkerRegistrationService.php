<?php

namespace App\Services\Common;

use App\Constants\Commons\OtpTypeConst;
use App\Models\User;
use App\Services\AbstractService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WorkerRegistrationService extends AbstractService
{
    private const WORKER_ROLE = 'worker';

    private const PENDING_KYC_STATUS = 'pending_kyc';

    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Register a new worker with KYC documents
     *
     * @throws Exception
     */
    public function register(array $data, array $files): User
    {
        $this->beginTransaction();
        try {
            // Upload files
            $idCardFrontUrl = $this->uploadFile($files['id_card_front'], 'kyc');
            $idCardBackUrl = $this->uploadFile($files['id_card_back'], 'kyc');
            $selfieUrl = $this->uploadFile($files['selfie'], 'kyc');

            // Create User
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => bcrypt($data['password']),
                'role' => self::WORKER_ROLE,
                'status' => self::PENDING_KYC_STATUS,
            ]);

            // Create WorkerProfile
            $user->workerProfile()->create([
                'id_card_number' => $data['id_card_number'],
                'id_card_front_url' => $idCardFrontUrl,
                'id_card_back_url' => $idCardBackUrl,
                'selfie_url' => $selfieUrl,
                'kyc_status' => 'pending',
            ]);

            // Send OTP for phone verification
            if ($user->email) {
                $this->otpService->generateOtp($user->email, OtpTypeConst::REGISTER);
            }

            $this->commitTransaction();

            return $user;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Upload file to storage
     */
    private function uploadFile(UploadedFile $file, string $folder): string
    {
        $filename = uniqid().'_'.time().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');

        return Storage::disk('public')->url($path);
    }
}
