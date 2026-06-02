<?php

namespace App\Services\User;

use App\Constants\Commons\CommonAuthConst;
use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\CustomerProfile\CustomerProfileRepository;
use App\Services\Common\BaseAuthService;
use Illuminate\Support\Facades\Hash;

class CustomerProfileService extends BaseAuthService
{
    protected $profileRepository;

    public function __construct(
        CustomerProfileRepository $profileRepository
    ) {
        $this->profileRepository = $profileRepository;
    }

    protected function guardName(): string
    {
        return CommonAuthConst::GUARD_API;
    }

    public function guard()
    {
        return auth()->guard($this->guardName());
    }

    public function getProfile()
    {
        $user = $this->guard()->user();
        if (! $user) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, __('auth.unauthenticated'), 401);
        }

        $profile = $this->profileRepository->findWhere([
            'user_id' => $user->id,
        ])->first();

        $profile?->load(['area.parent', 'avatar']);

        return [
            'user' => $user,
            'profile' => $profile,
        ];
    }

    public function updateProfile(array $data)
    {
        $user = $this->guard()->user();

        $this->beginTransaction();
        try {
            $updateData = [];

            if (array_key_exists('gender', $data)) {
                $updateData['gender'] = $data['gender'];
            }
            if (array_key_exists('dob', $data)) {
                $updateData['birthday'] = $data['dob'];
            }
            if (array_key_exists('phone', $data)) {
                $updateData['phone'] = $data['phone'];
            }
            if (array_key_exists('area_id', $data)) {
                $updateData['area_id'] = $data['area_id'];
            }

            if (isset($data['avatar_code'])) {
                $updateData['avatar_code'] = $data['avatar_code'];
            }

            $this->profileRepository->updateOrCreate(
                ['user_id' => $user->id],
                $updateData
            );

            // Update user basics if needed (name)
            if (isset($data['name'])) {
                $user->name = $data['name'];
                $user->save();
            }

            $this->commitTransaction();

            return $this->getProfile();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    public function changePassword(array $data)
    {
        $user = $this->guard()->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw new BusinessException(ExceptionCode::CURRENT_PASSWORD_NOT_MATCH, 'Current password does not match', 400);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        return true;
    }

    protected function handleException(\Throwable $e)
    {
        if ($e instanceof BusinessException) {
            throw $e;
        }

        throw new BusinessException(
            ExceptionCode::UNKNOWN_ERROR,
            $e->getMessage(),
            500
        );
    }
}
