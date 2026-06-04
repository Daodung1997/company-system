<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UpdateCompanySettingRequest;
use App\Http\Resources\Master\CompanySettingResource;
use App\Services\CompanySetting\CompanySettingService;
use App\Supports\Facades\Response\Response;

class CompanySettingController extends Controller
{
    public function __construct(protected CompanySettingService $companySettingService) {}

    /**
     * GET /api/master/company-setting - Get company settings
     */
    public function show()
    {
        $setting = $this->companySettingService->getSetting();

        return Response::success((new CompanySettingResource($setting))->resolve());
    }

    /**
     * POST /api/master/company-setting - Update company settings
     */
    public function update(UpdateCompanySettingRequest $request)
    {
        $setting = $this->companySettingService->updateSetting($request->validated());

        return Response::success((new CompanySettingResource($setting))->resolve());
    }
}
