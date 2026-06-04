<?php

namespace App\Services\CompanySetting;

use App\Repositories\CompanySetting\CompanySettingRepository;
use App\Services\AbstractService;

class CompanySettingService extends AbstractService
{
    public function __construct(
        protected CompanySettingRepository $companySettingRepository
    ) {}

    /**
     * Get the single company settings record.
     */
    public function getSetting()
    {
        $setting = $this->companySettingRepository->all()->first();

        if (!$setting) {
            // Create a default empty company setting
            $setting = $this->companySettingRepository->create([
                'company_name' => 'Công ty Giải pháp Công nghệ Việt Nam',
            ]);
        }

        return $setting;
    }

    /**
     * Update the company settings.
     */
    public function updateSetting(array $data)
    {
        $this->beginTransaction();
        try {
            $setting = $this->companySettingRepository->all()->first();

            if (!$setting) {
                $setting = $this->companySettingRepository->create($data);
            } else {
                $this->companySettingRepository->update($setting->id, $data);
                $setting = $this->companySettingRepository->find($setting->id);
            }

            $this->commitTransaction();
            return $setting;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
